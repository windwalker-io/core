<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Mailer\Subscriber;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\Mailer\Event\BeforeSendEvent;
use Windwalker\Core\Mailer\MailerInterface;
use Windwalker\Core\Mailer\MailMessage;
use Windwalker\Core\Mailer\Subscriber\MailInlinerSubscriber;

#[CoversClass(MailInlinerSubscriber::class)]
class MailInlinerSubscriberTest extends TestCase
{
    /**
     * @var string[]
     */
    private array $tempFiles = [];

    #[After]
    public function removeTempFiles(): void
    {
        foreach ($this->tempFiles as $tempFile) {
            if (is_file($tempFile)) {
                unlink($tempFile);
            }
        }

        $this->tempFiles = [];
    }

    private function createTempFile(string $suffix, string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'mailer-inliner-') . $suffix;
        file_put_contents($path, $content);

        $this->tempFiles[] = $path;

        return $path;
    }

    #[Test]
    public function beforeSendInlinesInternalStyles(): void
    {
        $message = new MailMessage();
        $message->html('<style>p { color: red; }</style><p>Hello World</p>');

        $event = new BeforeSendEvent(
            message: $message,
            mailer: $this->createStub(MailerInterface::class)
        );

        new MailInlinerSubscriber()->beforeSend($event);

        $html = $message->getHtmlBody();

        self::assertStringContainsString('style="color: red;"', $html);
        self::assertStringNotContainsString('<style>', $html);
        self::assertStringContainsString('Hello World', $html);
    }

    #[Test]
    public function beforeSendStripsScriptAndLinkTags(): void
    {
        $message = new MailMessage();
        $message->html(
            '<link rel="stylesheet" href="x.css"><script>alert(1)</script><p>Hello</p>'
        );

        $event = new BeforeSendEvent(
            message: $message,
            mailer: $this->createStub(MailerInterface::class)
        );

        new MailInlinerSubscriber()->beforeSend($event);

        $html = $message->getHtmlBody();

        self::assertStringNotContainsString('<script', $html);
        self::assertStringNotContainsString('<link', $html);
        self::assertStringContainsString('Hello', $html);
    }

    #[Test]
    public function beforeSendAddsCssFromAssetStyles(): void
    {
        $asset = $this->createStub(AssetService::class);
        $asset->method('getStyles')->willReturn(
            [
                ['url' => 'nonexistent-style.css'],
            ]
        );
        $asset->method('normalizeUri')->willReturn(['/no/such/file.css', '/no/such/file.min.css']);
        $asset->method('addSysPath')->willReturnArgument(0);

        $message = new class () extends MailMessage {
            public ?AssetService $assetOverride = null;

            public function getAsset(): ?AssetService
            {
                return $this->assetOverride;
            }
        };
        $message->assetOverride = $asset;
        $message->html('<p>Hello</p>');

        $event = new BeforeSendEvent(
            message: $message,
            mailer: $this->createStub(MailerInterface::class)
        );

        new MailInlinerSubscriber()->beforeSend($event);

        self::assertStringContainsString('Hello', $message->getHtmlBody());
    }

    #[Test]
    public function beforeSendLoadsMinifiedCssFileWhenPresent(): void
    {
        $minFile = $this->createTempFile('.min.css', 'p { color: blue; }');

        $asset = $this->createStub(AssetService::class);
        $asset->method('getStyles')->willReturn(
            [
                ['url' => 'style.css'],
            ]
        );
        $asset->method('normalizeUri')->willReturn(['/no/such/style.css', $minFile]);
        $asset->method('addSysPath')->willReturnArgument(0);

        $message = new class () extends MailMessage {
            public ?AssetService $assetOverride = null;

            public function getAsset(): ?AssetService
            {
                return $this->assetOverride;
            }
        };
        $message->assetOverride = $asset;
        $message->html('<p>Hello</p>');

        $event = new BeforeSendEvent(
            message: $message,
            mailer: $this->createStub(MailerInterface::class)
        );

        new MailInlinerSubscriber()->beforeSend($event);

        self::assertStringContainsString('style="color: blue;"', $message->getHtmlBody());
    }

    #[Test]
    public function beforeSendLoadsPlainCssFileWhenMinifiedFileIsMissing(): void
    {
        $cssFile = $this->createTempFile('.css', 'p { color: green; }');

        $asset = $this->createStub(AssetService::class);
        $asset->method('getStyles')->willReturn(
            [
                ['url' => 'style.css'],
            ]
        );
        $asset->method('normalizeUri')->willReturn([$cssFile, '/no/such/style.min.css']);
        $asset->method('addSysPath')->willReturnArgument(0);

        $message = new class () extends MailMessage {
            public ?AssetService $assetOverride = null;

            public function getAsset(): ?AssetService
            {
                return $this->assetOverride;
            }
        };
        $message->assetOverride = $asset;
        $message->html('<p>Hello</p>');

        $event = new BeforeSendEvent(
            message: $message,
            mailer: $this->createStub(MailerInterface::class)
        );

        new MailInlinerSubscriber()->beforeSend($event);

        self::assertStringContainsString('style="color: green;"', $message->getHtmlBody());
    }

    #[Test]
    public function beforeSendIgnoresUnexpectedValueExceptionFromAssetNormalization(): void
    {
        $asset = $this->createStub(AssetService::class);
        $asset->method('getStyles')->willReturn(
            [
                ['url' => 'broken-style.css'],
            ]
        );
        $asset->method('normalizeUri')->willThrowException(
            new UnexpectedValueException('Cannot normalize uri.')
        );
        $asset->method('addSysPath')->willReturnArgument(0);

        $message = new class () extends MailMessage {
            public ?AssetService $assetOverride = null;

            public function getAsset(): ?AssetService
            {
                return $this->assetOverride;
            }
        };
        $message->assetOverride = $asset;
        $message->html('<p>Hello</p>');

        $event = new BeforeSendEvent(
            message: $message,
            mailer: $this->createStub(MailerInterface::class)
        );

        new MailInlinerSubscriber()->beforeSend($event);

        self::assertStringContainsString('Hello', $message->getHtmlBody());
    }
}
