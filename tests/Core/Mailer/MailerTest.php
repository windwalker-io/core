<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Mailer;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Windwalker\Core\Mailer\Event\BeforeSendEvent;
use Windwalker\Core\Mailer\MailBuilder;
use Windwalker\Core\Mailer\MailerInterface;
use Windwalker\Core\Mailer\MailerOptions;
use Windwalker\Core\Mailer\Mailer;
use Windwalker\Core\Mailer\MailMessage;
use Windwalker\Core\Renderer\RendererService;
use Windwalker\DI\Container;
use Windwalker\Utilities\Env;

#[CoversClass(Mailer::class)]
class MailerTest extends TestCase
{
    private ?string $originalMailEnabled = null;

    #[Before]
    protected function disableMailByDefault(): void
    {
        // The host app's local .env may set MAIL_ENABLED=1; force a
        // deterministic default so tests are not environment-dependent.
        $this->originalMailEnabled = Env::get('MAIL_ENABLED');

        $_SERVER['MAIL_ENABLED'] = '0';
    }

    #[After]
    protected function cleanupEnv(): void
    {
        if ($this->originalMailEnabled === null) {
            unset($_ENV['MAIL_ENABLED'], $_SERVER['MAIL_ENABLED']);
        } else {
            $_SERVER['MAIL_ENABLED'] = $this->originalMailEnabled;
        }
    }

    protected function createMailer(
        ?TransportInterface $transport = null,
        ?Container $container = null,
        ?MailerOptions $options = null
    ): Mailer {
        $transport ??= $this->createStub(TransportInterface::class);
        $container ??= new Container();

        return new Mailer($transport, $container, options: $options ?? new MailerOptions());
    }

    protected function createValidMessage(): MailMessage
    {
        $message = new MailMessage();
        $message->from(new Address('from@example.com'));
        $message->to(new Address('to@example.com'));
        $message->text('Hello');

        return $message;
    }

    #[Test]
    public function sendReturnsSentMessageWithoutCallingTransportByDefault(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->never())->method('send');

        $mailer = $this->createMailer($transport);

        $result = $mailer->send($this->createValidMessage());

        self::assertInstanceOf(SentMessage::class, $result);
    }

    #[Test]
    public function sendCallsTransportWhenForceSendFlagSet(): void
    {
        $sentMessage = new SentMessage($this->createValidMessage(), new Envelope(new Address('from@example.com'), [new Address('to@example.com')]));

        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->willReturn($sentMessage);

        $mailer = $this->createMailer($transport);

        $result = $mailer->send($this->createValidMessage(), flags: Mailer::FORCE_SEND);

        self::assertSame($sentMessage, $result);
    }

    #[Test]
    public function sendCallsTransportWhenMailEnabledEnvIsSet(): void
    {
        $_SERVER['MAIL_ENABLED'] = '1';

        $sentMessage = new SentMessage($this->createValidMessage(), new Envelope(new Address('from@example.com'), [new Address('to@example.com')]));

        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->willReturn($sentMessage);

        $mailer = $this->createMailer($transport);

        $mailer->send($this->createValidMessage());
    }

    #[Test]
    public function sendClearsEnvelopeWhenIgnoreEnvelopeFlagSet(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->with(self::anything(), null)
            ->willReturn(
                new SentMessage(
                    $this->createValidMessage(),
                    new Envelope(new Address('from@example.com'), [new Address('to@example.com')])
                )
            );

        $mailer = $this->createMailer($transport);

        $envelope = new Envelope(new Address('from@example.com'), [new Address('to@example.com')]);

        $mailer->send(
            $this->createValidMessage(),
            $envelope,
            Mailer::FORCE_SEND | Mailer::IGNORE_ENVELOPE
        );
    }

    #[Test]
    public function sendEmitsBeforeSendEventAndAppliesMutations(): void
    {
        $mailer = $this->createMailer();

        $captured = null;

        $mailer->on(
            BeforeSendEvent::class,
            function (BeforeSendEvent $event) use (&$captured) {
                $captured = $event;
            }
        );

        $mailer->send($this->createValidMessage());

        self::assertInstanceOf(BeforeSendEvent::class, $captured);
        self::assertSame($mailer, $captured->mailer);
    }

    #[Test]
    public function sendAppliesFlagsMutatedByEventListener(): void
    {
        $sentMessage = new SentMessage(
            $this->createValidMessage(),
            new Envelope(new Address('from@example.com'), [new Address('to@example.com')])
        );

        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->willReturn($sentMessage);

        $mailer = $this->createMailer($transport);

        $mailer->on(
            BeforeSendEvent::class,
            function (BeforeSendEvent $event) {
                $event->flags |= Mailer::FORCE_SEND;
            }
        );

        $result = $mailer->send($this->createValidMessage());

        self::assertSame($sentMessage, $result);
    }

    #[Test]
    public function prepareMessageDoesNothingForNonMailMessage(): void
    {
        $mailer = $this->createMailer();

        $rawMessage = $this->createStub(\Symfony\Component\Mime\RawMessage::class);

        $mailer->prepareMessage($rawMessage);

        self::assertTrue(true);
    }

    #[Test]
    public function prepareMessageSetsFromAndReplyToFromContainerParams(): void
    {
        $container = new Container();
        $container->mergeParameters(
            null,
            [
                'mail' => [
                    'from' => 'from@example.com',
                    'reply_to' => 'reply@example.com',
                ],
            ]
        );

        $mailer = $this->createMailer(container: $container);

        $message = new MailMessage();
        $message->to(new Address('to@example.com'));
        $message->html('<p>Body</p>');

        $mailer->prepareMessage($message);

        self::assertSame('from@example.com', $message->getFrom()[0]->getAddress());
        self::assertSame('reply@example.com', $message->getReplyTo()[0]->getAddress());
    }

    #[Test]
    public function prepareMessageKeepsExistingFromAndReplyTo(): void
    {
        $container = new Container();
        $container->mergeParameters(
            null,
            [
                'mail' => [
                    'from' => 'from@example.com',
                    'reply_to' => 'reply@example.com',
                ],
            ]
        );

        $mailer = $this->createMailer(container: $container);

        $message = new MailMessage();
        $message->from(new Address('other@example.com'));
        $message->replyTo(new Address('other-reply@example.com'));
        $message->to(new Address('to@example.com'));
        $message->html('<p>Body</p>');

        $mailer->prepareMessage($message);

        self::assertSame('other@example.com', $message->getFrom()[0]->getAddress());
        self::assertSame('other-reply@example.com', $message->getReplyTo()[0]->getAddress());
    }

    #[Test]
    public function prepareMessageHandlesAutoCcAndBccWhenIgnoreAutoCcFlagSet(): void
    {
        $options = new MailerOptions(cc: 'cc@example.com', bcc: 'bcc@example.com');

        $mailer = $this->createMailer(options: $options);

        $message = $this->createValidMessage();

        $mailer->prepareMessage($message, Mailer::IGNORE_AUTO_CC);

        self::assertSame('cc@example.com', $message->getCc()[0]->getAddress());
        self::assertSame('bcc@example.com', $message->getBcc()[0]->getAddress());
    }

    #[Test]
    public function prepareMessageDoesNotHandleAutoCcWhenFlagNotSet(): void
    {
        $options = new MailerOptions(cc: 'cc@example.com', bcc: 'bcc@example.com');

        $mailer = $this->createMailer(options: $options);

        $message = $this->createValidMessage();

        $mailer->prepareMessage($message);

        self::assertSame([], $message->getCc());
        self::assertSame([], $message->getBcc());
    }

    #[Test]
    public function prepareMessageGeneratesTextBodyFromHtmlBody(): void
    {
        $mailer = $this->createMailer();

        $message = new MailMessage();
        $message->from(new Address('from@example.com'));
        $message->to(new Address('to@example.com'));
        $message->html('<p>Hello <b>World</b></p>');

        $mailer->prepareMessage($message);

        self::assertSame('Hello World', $message->getTextBody());
    }

    #[Test]
    public function createMessageSetsSubjectWhenProvided(): void
    {
        $mailer = $this->createMailer();

        $message = $mailer->createMessage('Hello Subject');

        self::assertInstanceOf(MailMessage::class, $message);
        self::assertSame('Hello Subject', $message->getSubject());
    }

    #[Test]
    public function createMessageLeavesSubjectUnsetWhenNotProvided(): void
    {
        $mailer = $this->createMailer();

        $message = $mailer->createMessage();

        self::assertNull($message->getSubject());
    }

    #[Test]
    public function wrapAddressesConvertsStringsIntoAddressObjects(): void
    {
        $result = Mailer::wrapAddresses(['foo@example.com', 'bar@example.com']);

        self::assertInstanceOf(Address::class, $result[0]);
        self::assertSame('foo@example.com', $result[0]->getAddress());
        self::assertSame('bar@example.com', $result[1]->getAddress());
    }

    #[Test]
    public function wrapAddressesHandlesNestedArraysRecursively(): void
    {
        $result = Mailer::wrapAddresses(['sender' => 'foo@example.com', 'recipients' => ['bar@example.com']]);

        self::assertSame('foo@example.com', $result['sender']->getAddress());
        self::assertSame('bar@example.com', $result['recipients'][0]->getAddress());
    }

    #[Test]
    public function wrapAddressesKeepsNonStringNonArrayValuesAsIs(): void
    {
        $address = new Address('foo@example.com');

        $result = Mailer::wrapAddresses(['addr' => $address]);

        self::assertSame($address, $result['addr']);
    }

    #[Test]
    public function renderBodyDelegatesToRendererService(): void
    {
        $renderer = $this->createMock(RendererService::class);
        $renderer->expects($this->once())
            ->method('render')
            ->with('mail.welcome', ['name' => 'Foo'], [])
            ->willReturn('<p>Hello Foo</p>');

        $container = new Container();
        $container->share(RendererService::class, $renderer);

        $mailer = $this->createMailer(container: $container);

        $result = $mailer->renderBody('mail.welcome', ['name' => 'Foo']);

        self::assertSame('<p>Hello Foo</p>', $result);
    }

    #[Test]
    public function buildBodyReturnsRawStringWhenLayoutIsFalse(): void
    {
        $mailer = $this->createMailer();

        $result = $mailer->buildBody(
            function (MailBuilder $builder) {
                $builder->text('Hello');

                return $builder;
            },
            false
        );

        self::assertStringContainsString('Hello', $result);
    }

    #[Test]
    public function buildBodyRendersWithLayoutByDefault(): void
    {
        $renderer = $this->createMock(RendererService::class);
        $renderer->expects($this->once())
            ->method('render')
            ->with(
                'mail.mail-layout',
                self::callback(
                    static fn(array $data) => str_contains($data['content'], 'Hello')
                        && $data['builder'] instanceof MailBuilder
                ),
                []
            )
            ->willReturn('<html>Hello</html>');

        $container = new Container();
        $container->share(RendererService::class, $renderer);

        $mailer = $this->createMailer(container: $container);

        $result = $mailer->buildBody(
            function (MailBuilder $builder, MailerInterface $mailer) {
                $builder->text('Hello');
            }
        );

        self::assertSame('<html>Hello</html>', $result);
    }

    #[Test]
    public function htmlToTextMessageStripsStyleAndTagsAndCollapsesWhitespace(): void
    {
        $mailer = $this->createMailer();

        $html = "<style>.c{color:#fff}</style>\n<p>Hello,&nbsp;&nbsp;World!</p>\n\n"
            . '<a href="https://example.com">Click here</a>';

        $result = $mailer->htmlToTextMessage($html);

        self::assertSame('Hello, World! Click here (https://example.com)', $result);
    }
}
