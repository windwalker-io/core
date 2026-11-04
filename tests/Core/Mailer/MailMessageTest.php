<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Mailer;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;
use Symfony\Component\Mime\RawMessage;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\Mailer\BuilderMailerInterface;
use Windwalker\Core\Mailer\MailerInterface;
use Windwalker\Core\Mailer\MailMessage;
use Windwalker\Core\Mailer\RenderableMailerInterface;

#[CoversClass(MailMessage::class)]
class MailMessageTest extends TestCase
{
    #[Test]
    public function sendDelegatesToMailer(): void
    {
        $envelope = new Envelope(new Address('from@example.com'), [new Address('to@example.com')]);
        $sentMessage = $this->createStub(SentMessage::class);

        $mailer = $this->createMock(MailerInterface::class);
        $message = new MailMessage($mailer);

        $mailer->expects($this->once())
            ->method('send')
            ->with($message, $envelope, 1)
            ->willReturn($sentMessage);

        $result = $message->send($envelope, 1);

        self::assertSame($sentMessage, $result);
    }

    #[Test]
    public function sendThrowsLogicExceptionWhenMailerIsNull(): void
    {
        $message = new MailMessage();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains('must set into');

        $message->send();
    }

    #[Test]
    public function toStringReturnsPlainRenderWhenNotDebug(): void
    {
        $message = new MailMessage();
        $message->from(new Address('from@example.com'));
        $message->to(new Address('to@example.com'));
        $message->text('Hello');

        self::assertSame($this->stripMessageId($message->toString()), $this->stripMessageId($message->toString(false)));
    }

    #[Test]
    public function toStringReturnsPlainRenderWhenMailerNotRenderable(): void
    {
        $mailer = $this->createStub(MailerInterface::class);

        $message = new MailMessage($mailer);
        $message->from(new Address('from@example.com'));
        $message->to(new Address('to@example.com'));
        $message->text('Hello');

        self::assertSame(
            $this->stripMessageId($message->toString(false)),
            $this->stripMessageId($message->toString(true))
        );
    }

    private function stripMessageId(string $raw): string
    {
        return preg_replace('/^Message-ID: .*$/m', 'Message-ID: <stripped>', $raw);
    }

    #[Test]
    public function toStringPreparesClonedMessageWhenDebugAndMailerRenderable(): void
    {
        $mailer = new MailMessageTestRenderablePreparingMailer();

        $message = new MailMessage($mailer);
        $message->from(new Address('from@example.com'));
        $message->to(new Address('to@example.com'));
        $message->text('Hello');

        $result = $message->toString(true);

        self::assertNotNull($mailer->prepared);
        self::assertNotSame($message, $mailer->prepared);
        self::assertStringContainsString('X-Prepared: 1', $result);
        self::assertNull($message->getHeaders()->get('X-Prepared'));
    }

    #[Test]
    public function renderBodyRendersHtmlWhenMailerIsRenderable(): void
    {
        $mailer = $this->createMockForIntersectionOfInterfaces(
            [MailerInterface::class, RenderableMailerInterface::class]
        );
        $asset = $this->createStub(AssetService::class);

        $mailer->expects($this->once())
            ->method('renderBody')
            ->with(
                'mail.welcome',
                $this->callback(
                    static fn(array $data) => isset($data['message'], $data['asset']) && $data['asset'] === $asset
                )
            )
            ->willReturn('<p>Rendered</p>');
        $mailer->method('createAssetService')->willReturn($asset);

        $message = new MailMessage($mailer);

        $result = $message->renderBody('mail.welcome');

        self::assertSame($message, $result);
        self::assertSame('<p>Rendered</p>', $message->getHtmlBody());
    }

    #[Test]
    public function renderBodyDoesNothingWhenMailerIsNotRenderable(): void
    {
        $mailer = $this->createStub(MailerInterface::class);

        $message = new MailMessage($mailer);

        $result = $message->renderBody('mail.welcome');

        self::assertSame($message, $result);
        self::assertNull($message->getHtmlBody());
    }

    #[Test]
    public function buildBodyDelegatesToMailerWhenBuilderMailer(): void
    {
        $mailer = $this->createMockForIntersectionOfInterfaces(
            [MailerInterface::class, BuilderMailerInterface::class]
        );

        $mailer->expects($this->once())
            ->method('buildBody')
            ->willReturn('<p>Built</p>');

        $message = new MailMessage($mailer);

        $result = $message->buildBody(static fn() => null);

        self::assertSame($message, $result);
        self::assertSame('<p>Built</p>', $message->getHtmlBody());
    }

    #[Test]
    public function buildBodyDoesNothingWhenMailerIsNotBuilderMailer(): void
    {
        $mailer = $this->createStub(MailerInterface::class);

        $message = new MailMessage($mailer);

        $result = $message->buildBody(static fn() => null);

        self::assertSame($message, $result);
        self::assertNull($message->getHtmlBody());
    }

    #[Test]
    public function attachPsr7AttachesStreamContent(): void
    {
        $stream = $this->createStub(StreamInterface::class);
        $stream->method('getContents')->willReturn('file-contents');

        $message = new MailMessage();

        $result = $message->attachPsr7($stream, 'file.txt', 'text/plain');

        self::assertSame($message, $result);
        self::assertCount(1, $message->getAttachments());
    }

    #[Test]
    public function unsubscribeAddsHttpAndMailtoLinks(): void
    {
        $message = new MailMessage();

        $message->unsubscribe('https://example.com/unsub', 'unsub@example.com');

        $header = $message->getHeaders()->get('List-Unsubscribe');

        self::assertNotNull($header);
        self::assertStringContainsString('<https://example.com/unsub>', $header->getBodyAsString());
        self::assertStringContainsString('<mailto:unsub@example.com>', $header->getBodyAsString());
        self::assertNull($message->getHeaders()->get('List-Unsubscribe-Post'));
    }

    #[Test]
    public function unsubscribeAddsOneClickHeaderWhenRequested(): void
    {
        $message = new MailMessage();

        $message->unsubscribe('https://example.com/unsub', null, true);

        $header = $message->getHeaders()->get('List-Unsubscribe-Post');

        self::assertNotNull($header);
        self::assertSame('List-Unsubscribe=One-Click', $header->getBodyAsString());
    }

    #[Test]
    public function unsubscribeSkipsListUnsubscribeHeaderWhenNoLinksProvided(): void
    {
        $message = new MailMessage();

        $message->unsubscribe(null);

        self::assertNull($message->getHeaders()->get('List-Unsubscribe'));
    }

    #[Test]
    public function debugPrintBodyEchoesHtmlBodyAndReturnsSelf(): void
    {
        $message = new MailMessage();
        $message->html('<p>Debug Body</p>');

        $this->expectOutputString('<p>Debug Body</p>');

        $result = $message->debugPrintBody(false);

        self::assertSame($message, $result);
    }
}

/**
 * Test-only stub mailer implementing the duck-typed `prepareMessage()` contract that
 * {@see MailMessage::toString()} relies on for its debug-render branch.
 *
 * `prepareMessage()` is not declared on {@see RenderableMailerInterface}, so a plain mock of that
 * interface cannot satisfy the call; this fixture exists to exercise that branch. Remove it if
 * `MailMessage::toString()` stops calling `prepareMessage()` directly.
 */
class MailMessageTestRenderablePreparingMailer implements MailerInterface, RenderableMailerInterface
{
    public ?RawMessage $prepared = null;

    public function createMessage(
        ?string $subject = null,
        ?Headers $headers = null,
        ?AbstractPart $body = null
    ): MailMessage {
        return new MailMessage($this);
    }

    public function send(MailMessage $message, ?Envelope $envelope = null, int $flags = 0): SentMessage
    {
        throw new LogicException('Not implemented in test fixture.');
    }

    public function renderBody(string $layout, array $data = [], array $options = []): string
    {
        return '';
    }

    public function createAssetService(): AssetService
    {
        throw new LogicException('Not implemented in test fixture.');
    }

    public function prepareMessage(RawMessage $message, int $flags = 0): void
    {
        $this->prepared = $message;
        $message->getHeaders()->addTextHeader('X-Prepared', '1');
    }
}
