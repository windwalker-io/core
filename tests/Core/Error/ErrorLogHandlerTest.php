<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Error;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\AppVerbosity;
use Windwalker\Core\Error\ErrorLogHandler;
use Windwalker\Core\Runtime\Config;
use Windwalker\Core\Service\LoggerService;

#[CoversClass(ErrorLogHandler::class)]
class ErrorLogHandlerTest extends TestCase
{
    #[Test]
    public function invokeDoesNothingWhenDisabled(): void
    {
        $logger = $this->createMock(LoggerService::class);
        $logger->expects($this->never())->method('error');

        $handler = new ErrorLogHandler($logger, new Config(), AppVerbosity::NORMAL, ['enabled' => false]);

        $handler(new Exception('Boom'));

        self::assertSame('', $this->getActualOutputForAssertion());
    }

    #[Test]
    public function invokeLogsServerErrors(): void
    {
        $exception = new Exception('Boom', 500);

        $logger = $this->createMock(LoggerService::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('error', 'Boom', ['exception' => $exception]);

        $handler = new ErrorLogHandler($logger, new Config(), AppVerbosity::NORMAL, ['enabled' => true]);

        $handler($exception);
    }

    #[Test]
    public function invokeIgnores40xByDefault(): void
    {
        $logger = $this->createMock(LoggerService::class);
        $logger->expects($this->never())->method('error');

        $handler = new ErrorLogHandler($logger, new Config(), AppVerbosity::NORMAL, ['enabled' => true]);

        $handler(new Exception('Not Found', 404));

        self::assertSame('', $this->getActualOutputForAssertion());
    }

    #[Test]
    public function invokeLogs40xWhenIgnore40xDisabled(): void
    {
        $exception = new Exception('Not Found', 404);

        $logger = $this->createMock(LoggerService::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('error', 'Not Found', ['exception' => $exception]);

        $handler = new ErrorLogHandler(
            $logger,
            new Config(),
            AppVerbosity::NORMAL,
            ['enabled' => true, 'ignore_40x' => false]
        );

        $handler($exception);
    }

    #[Test]
    public function invokePrintsMessageWhenPrintOptionEnabled(): void
    {
        $logger = $this->createStub(LoggerService::class);

        $handler = new ErrorLogHandler(
            $logger,
            new Config(),
            AppVerbosity::NORMAL,
            ['enabled' => true, 'print' => true]
        );

        $handler(new Exception('Boom', 500));

        self::assertSame('Boom', $this->getActualOutputForAssertion());
    }

    #[Test]
    public function invokeSkipsLoggingWhenChannelIsEmptyButStillPrints(): void
    {
        $logger = $this->createMock(LoggerService::class);
        $logger->expects($this->never())->method('error');

        $handler = new ErrorLogHandler(
            $logger,
            new Config(),
            AppVerbosity::NORMAL,
            ['enabled' => true, 'channel' => '', 'print' => true]
        );

        $handler(new Exception('Boom', 500));

        self::assertSame('Boom', $this->getActualOutputForAssertion());
    }

    #[Test]
    public function invokeIncludesExceptionInContextWhenBacktracesEnabled(): void
    {
        $exception = new Exception('Boom', 500);

        $logger = $this->createMock(LoggerService::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('error', 'Boom', ['exception' => $exception]);

        $handler = new ErrorLogHandler(
            $logger,
            new Config(),
            AppVerbosity::NORMAL,
            ['enabled' => true, 'backtraces' => true]
        );

        $handler($exception);
    }

    #[Test]
    public function invokeExcludesExceptionFromContextWhenBacktracesDisabled(): void
    {
        $logger = $this->createMock(LoggerService::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('error', 'Boom', []);

        $handler = new ErrorLogHandler(
            $logger,
            new Config(),
            AppVerbosity::NORMAL,
            ['enabled' => true, 'backtraces' => false]
        );

        $handler(new Exception('Boom', 500));
    }

    #[Test]
    public function constructorResolvesChannelFromConfig(): void
    {
        $exception = new Exception('Boom', 500);

        $logger = $this->createMock(LoggerService::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('custom-channel', 'Boom', ['exception' => $exception]);

        $config = new Config(['error' => ['log_channel' => 'custom-channel']]);

        $handler = new ErrorLogHandler($logger, $config, AppVerbosity::NORMAL, ['enabled' => true]);

        $handler($exception);
    }

    #[Test]
    public function constructorFallsBackToDefaultChannelWhenConfigIsEmpty(): void
    {
        $exception = new Exception('Boom', 500);

        $logger = $this->createMock(LoggerService::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('error', 'Boom', ['exception' => $exception]);

        $handler = new ErrorLogHandler($logger, new Config(), AppVerbosity::NORMAL, ['enabled' => true]);

        $handler($exception);
    }

    #[Test]
    public function handleExceptionLogTextFormatsMessageAndBacktraces(): void
    {
        $text = self::triggerAndFormat();

        self::assertStringStartsWith(
            sprintf('Code: 0 - Boom - File: %s (', __FILE__),
            $text
        );
        self::assertStringContainsString('#1 - ', $text);
    }

    private static function triggerAndFormat(): string
    {
        return self::nestedCallForTrace();
    }

    private static function nestedCallForTrace(): string
    {
        return ErrorLogHandler::handleExceptionLogText(new Exception('Boom'), dirname(__DIR__, 3));
    }
}
