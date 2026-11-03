<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Service;

use ErrorException;
use Exception;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Throwable;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\AppVerbosity;
use Windwalker\Core\Error\ErrorHandlerInterface;
use Windwalker\Core\Runtime\Config;
use Windwalker\Core\Service\ErrorService;
use Windwalker\DI\Exception\DependencyResolutionException;

#[CoversClass(ErrorService::class)]
class ErrorServiceTest extends TestCase
{
    private ?string $originalDeprecationAsFatalEnv = null;

    private ?int $originalErrorReporting = null;

    #[Before]
    protected function saveGlobalErrorState(): void
    {
        $this->originalDeprecationAsFatalEnv = $_SERVER['DEPRECATION_AS_FATAL_ERROR']
            ?? $_ENV['DEPRECATION_AS_FATAL_ERROR']
            ?? null;
        $this->originalErrorReporting = error_reporting();

        unset($_SERVER['DEPRECATION_AS_FATAL_ERROR'], $_ENV['DEPRECATION_AS_FATAL_ERROR']);
    }

    #[After]
    protected function restoreGlobalErrorState(): void
    {
        unset($_SERVER['DEPRECATION_AS_FATAL_ERROR'], $_ENV['DEPRECATION_AS_FATAL_ERROR']);

        if ($this->originalDeprecationAsFatalEnv !== null) {
            $_SERVER['DEPRECATION_AS_FATAL_ERROR'] = $this->originalDeprecationAsFatalEnv;
        }

        error_reporting($this->originalErrorReporting);
    }

    private function createApp(bool $verbose = false): ApplicationInterface
    {
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('isVerbose')->willReturn($verbose);

        return $app;
    }

    private function createService(array $configData = [], ?ApplicationInterface $app = null): ErrorService
    {
        return new ErrorService(new Config($configData), $app ?? $this->createApp());
    }

    #[Test]
    public function shouldLogErrorReturnsTrueByDefault(): void
    {
        $service = $this->createService();

        self::assertTrue($service->shouldLogError());
    }

    #[Test]
    public function shouldLogErrorReturnsConfiguredValue(): void
    {
        $service = $this->createService(['error' => ['log' => false]]);

        self::assertFalse($service->shouldLogError());
    }

    #[Test]
    public function errorLogChannelReturnsErrorByDefault(): void
    {
        $service = $this->createService();

        self::assertSame('error', $service->errorLogChannel());
    }

    #[Test]
    public function errorLogChannelReturnsConfiguredChannel(): void
    {
        $service = $this->createService(['error' => ['log_channel' => 'custom']]);

        self::assertSame('custom', $service->errorLogChannel());
    }

    #[Test]
    public function logErrorRetrievesTaggedLoggerAndLogsMessage(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with(LogLevel::ERROR, 'Boom', ['foo' => 'bar']);

        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('retrieve')
            ->with(LoggerInterface::class, false, 'error')
            ->willReturn($logger);

        $service = $this->createService(app: $app);

        self::assertSame($service, $service->logError('Boom', ['foo' => 'bar']));
    }

    #[Test]
    public function logExceptionUsesResolvedHandlerWhenAvailable(): void
    {
        $exception = new Exception('Boom');

        $handler = $this->createMock(ErrorHandlerInterface::class);
        $handler->expects($this->once())->method('__invoke')->with($exception);

        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('retrieve')
            ->with(ErrorHandlerInterface::class, false, 'log')
            ->willReturn($handler);

        $service = $this->createService(app: $app);

        self::assertSame($service, $service->logException($exception));
    }

    #[Test]
    public function logExceptionFallsBackToLogErrorWhenHandlerIsMissing(): void
    {
        $exception = new Exception('Boom');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with(LogLevel::ERROR, 'Boom', ['exception' => $exception]);

        $app = $this->createStub(ApplicationInterface::class);
        $app->method('retrieve')->willReturnCallback(
            function (string $id, bool $forceNew = false, ?string $tag = null) use ($logger) {
                if ($id === ErrorHandlerInterface::class) {
                    throw new DependencyResolutionException('No handler registered');
                }

                return $logger;
            }
        );
        $app->method('getVerbosity')->willReturn(AppVerbosity::NORMAL);

        $service = $this->createService(app: $app);

        self::assertSame($service, $service->logException($exception));
    }

    #[Test]
    public function resolveErrorHandlerReturnsHandlerFromContainer(): void
    {
        $handler = $this->createStub(ErrorHandlerInterface::class);

        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())
            ->method('retrieve')
            ->with(ErrorHandlerInterface::class, false, 'log')
            ->willReturn($handler);

        $service = $this->createService(app: $app);

        self::assertSame($handler, $service->resolveErrorHandler('log'));
    }

    #[Test]
    public function resolveErrorHandlerReturnsNullWhenContainerThrows(): void
    {
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('retrieve')->willThrowException(new DependencyResolutionException('Not found'));

        $service = $this->createService(app: $app);

        self::assertNull($service->resolveErrorHandler('log'));
    }

    #[Test]
    public function downDoesNothingWhenNoLastErrorRecorded(): void
    {
        error_clear_last();

        $service = $this->createService();
        $service->down();

        self::assertNull(error_get_last());
    }

    #[Test]
    public function downDoesNothingWhenLastErrorIsNotFatal(): void
    {
        error_clear_last();

        // The handler must return `false` so PHP's internal error reporting still
        // records the error into error_get_last(); returning `true` suppresses that.
        set_error_handler(static fn() => false);

        try {
            @trigger_error('Not fatal', E_USER_WARNING);
        } finally {
            restore_error_handler();
        }

        $service = $this->createService();
        $service->down();

        self::assertSame(E_USER_WARNING, error_get_last()['type']);
    }

    #[Test]
    public function errorReturnsSilentlyWhenReportingMaskExcludesCode(): void
    {
        error_reporting(0);

        $service = $this->createService();
        $service->error(E_WARNING, 'Boom', __FILE__, __LINE__);

        self::assertTrue(true);
    }

    #[Test]
    public function errorThrowsErrorExceptionForStandardCodesWhenReportingEnabled(): void
    {
        error_reporting(E_ALL);

        $service = $this->createService();

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Boom');

        $service->error(E_WARNING, 'Boom', 'file.php', 42);
    }

    #[Test]
    public function errorHandlesDeprecationWithoutThrowingWhenNotForcedFatal(): void
    {
        error_reporting(E_ALL);

        $app = $this->createStub(ApplicationInterface::class);
        $app->method('isVerbose')->willReturn(false);
        $app->method('call')->willReturnCallback(
            static fn(callable $callable, array $args = []) => $callable($args[ErrorException::class])
        );

        $service = $this->createService(app: $app);

        $captured = null;
        $service->addDeprecationHandler(
            static function (ErrorException $e) use (&$captured): void {
                $captured = $e;
            }
        );

        $service->error(E_USER_DEPRECATED, 'Deprecated thing', 'file.php', 10);

        self::assertInstanceOf(ErrorException::class, $captured);
        self::assertStringContainsString('Deprecated thing', $captured->getMessage());
    }

    #[Test]
    public function errorThrowsForDeprecationWhenAppIsVerbose(): void
    {
        error_reporting(E_ALL);

        $service = $this->createService(app: $this->createApp(verbose: true));

        $this->expectException(ErrorException::class);

        $service->error(E_USER_DEPRECATED, 'Deprecated', 'file.php', 1);
    }

    #[Test]
    public function errorThrowsForDeprecationWhenEnvForcesFatal(): void
    {
        error_reporting(E_ALL);

        $_SERVER['DEPRECATION_AS_FATAL_ERROR'] = '1';

        $service = $this->createService();

        $this->expectException(ErrorException::class);

        $service->error(E_DEPRECATED, 'Deprecated', 'file.php', 1);
    }

    #[Test]
    public function addHandlerStoresNamedAndPositionalHandlers(): void
    {
        $service = $this->createService();

        $named = static fn() => null;
        $positional = static fn() => null;

        $service->addHandler($named, 'first');
        $service->addHandler($positional);

        self::assertSame(['first' => $named, 0 => $positional], $service->getHandlers());
    }

    #[Test]
    public function removeHandlerRemovesByName(): void
    {
        $service = $this->createService();
        $service->addHandler(static fn() => null, 'first');

        self::assertSame($service, $service->removeHandler('first'));
        self::assertSame([], $service->getHandlers());
    }

    #[Test]
    public function setHandlersReplacesAllHandlers(): void
    {
        $service = $this->createService();
        $service->addHandler(static fn() => null, 'first');

        $handler = static fn() => null;
        self::assertSame($service, $service->setHandlers(['x' => $handler]));
        self::assertSame(['x' => $handler], $service->getHandlers());
    }

    #[Test]
    public function addDeprecationHandlerStoresNamedAndPositionalHandlers(): void
    {
        $service = $this->createService();

        $named = static fn() => null;
        $positional = static fn() => null;

        $service->addDeprecationHandler($named, 'first');
        $service->addDeprecationHandler($positional);

        self::assertSame(['first' => $named, 0 => $positional], $service->getDeprecationHandlers());
    }

    #[Test]
    public function removeDeprecationHandlerRemovesByName(): void
    {
        $service = $this->createService();
        $service->addDeprecationHandler(static fn() => null, 'first');

        self::assertSame($service, $service->removeDeprecationHandler('first'));
        self::assertSame([], $service->getDeprecationHandlers());
    }

    #[Test]
    public function setDeprecationHandlersReplacesAllHandlers(): void
    {
        $service = $this->createService();
        $service->addDeprecationHandler(static fn() => null, 'first');

        $handler = static fn() => null;
        self::assertSame($service, $service->setDeprecationHandlers(['x' => $handler]));
        self::assertSame(['x' => $handler], $service->getDeprecationHandlers());
    }

    #[Test]
    public function handleInvokesEachRegisteredHandlerWithException(): void
    {
        $exception = new Exception('Boom');

        $app = $this->createStub(ApplicationInterface::class);
        $app->method('call')->willReturnCallback(
            static fn(callable $callable, array $args = []) => $callable($args['exception'])
        );

        $service = $this->createService(app: $app);

        $received = [];
        $service->addHandler(
            static function (Throwable $e) use (&$received): void {
                $received[] = $e;
            }
        );
        $service->addHandler(
            static function (Throwable $e) use (&$received): void {
                $received[] = $e;
            },
            'named'
        );

        $service->handle($exception);

        self::assertCount(2, $received);
        self::assertSame($exception, $received[0]);
        self::assertSame($exception, $received[1]);
    }

    #[Test]
    public function getLevelNameReturnsNameForKnownConstant(): void
    {
        $service = $this->createService();

        self::assertSame('E_WARNING', $service->getLevelName(E_WARNING));
    }

    #[Test]
    public function getLevelNameReturnsNullForUnknownConstant(): void
    {
        $service = $this->createService();

        self::assertNull($service->getLevelName(123456789));
    }

    #[Test]
    public function getLevelCodeReturnsCodeForKnownNameCaseInsensitiveAndTrimmed(): void
    {
        $service = $this->createService();

        self::assertSame(E_WARNING, $service->getLevelCode(' e_warning '));
    }

    #[Test]
    public function getLevelCodeReturnsFalseForUnknownName(): void
    {
        $service = $this->createService();

        self::assertFalse($service->getLevelCode('NOT_A_LEVEL'));
    }

    #[Test]
    public function setErrorTemplateUpdatesTemplateAndOptionallyEngine(): void
    {
        $service = $this->createService();

        self::assertSame('windwalker.error.default', $service->getErrorTemplate());
        self::assertSame('php', $service->getEngine());

        $service->setErrorTemplate('custom.template');

        self::assertSame('custom.template', $service->getErrorTemplate());
        self::assertSame('php', $service->getEngine());

        $service->setErrorTemplate('custom2.template', 'blade');

        self::assertSame('custom2.template', $service->getErrorTemplate());
        self::assertSame('blade', $service->getEngine());
    }

    // The five tests below install real PHP error/exception handlers via
    // registerErrors()/registerExceptions()/register()/restore(). Those methods call
    // restore_error_handler()/restore_exception_handler(), which permanently discards
    // whatever handler PHPUnit itself had installed for the running test (there is no
    // API to peek below the top of the handler stack without popping it). PHPUnit's
    // leak detector therefore always reports these tests as "risky" ("removed error/
    // exception handlers other than its own"), even though PHPUnit transparently
    // restores its own handler for the next test afterwards. This is expected and
    // does not fail the run (no failOnRisky is configured).

    #[Test]
    public function registerErrorsInstallsServiceAsPhpErrorHandler(): void
    {
        error_reporting(E_ALL);

        $service = $this->createService();
        $service->registerErrors();

        $this->expectException(ErrorException::class);

        trigger_error('Boom', E_USER_WARNING);
    }

    #[Test]
    public function registerExceptionsInstallsServiceAsPhpExceptionHandler(): void
    {
        $service = $this->createService();
        $service->registerExceptions();

        // Peek at the currently active handler without disturbing the stack: pushing
        // `null` returns whatever was active, and `restore_exception_handler()`
        // immediately pops that `null` frame back off again.
        $previousHandler = set_exception_handler(null);
        restore_exception_handler();

        self::assertSame([$service, 'exception'], $previousHandler);
    }

    #[Test]
    public function restoreResetsBothHandlersToDefault(): void
    {
        $service = $this->createService();
        $service->registerErrors();
        $service->registerExceptions();

        $service->restore();

        // `restore()` only pops the handler frames this service pushed; what remains
        // underneath isn't observable without destructively unwinding the whole stack,
        // so we only assert that the service's own handlers are no longer active.
        $errorHandler = set_error_handler(null);
        restore_error_handler();
        $exceptionHandler = set_exception_handler(null);
        restore_exception_handler();

        self::assertNotSame([$service, 'error'], $errorHandler);
        self::assertNotSame([$service, 'exception'], $exceptionHandler);
    }

    #[Test]
    public function registerInstallsErrorAndExceptionHandlersByDefault(): void
    {
        $service = $this->createService();
        $service->register();

        $errorHandler = set_error_handler(null);
        restore_error_handler();
        $exceptionHandler = set_exception_handler(null);
        restore_exception_handler();

        self::assertSame([$service, 'error'], $errorHandler);
        self::assertSame([$service, 'exception'], $exceptionHandler);
    }

    #[Test]
    public function registerAlsoRegistersShutdownHandlerWhenRequested(): void
    {
        // `registerShutdown()` only registers `down()` via `register_shutdown_function()`,
        // which cannot be observed or verified from within the current process; the
        // assertions below only prove that requesting it does not break the rest of
        // `register()`'s observable behaviour.
        $service = $this->createService();
        $service->register(shutdown: true);

        $errorHandler = set_error_handler(null);
        restore_error_handler();
        $exceptionHandler = set_exception_handler(null);
        restore_exception_handler();

        self::assertSame([$service, 'error'], $errorHandler);
        self::assertSame([$service, 'exception'], $exceptionHandler);
    }

    #[Test]
    public function normalizeCodeReturnsValidThreeDigitStatusUnchanged(): void
    {
        self::assertSame(404, ErrorService::normalizeCode(404));
    }

    #[Test]
    public function normalizeCodeTruncatesLongCodesNotStartingWithTwoOrThree(): void
    {
        self::assertSame(404, ErrorService::normalizeCode(404123));
    }

    #[Test]
    public function normalizeCodeDoesNotTruncateWhenStartingWithTwoOrThree(): void
    {
        self::assertSame(500, ErrorService::normalizeCode(30000));
    }

    #[Test]
    public function normalizeCodeFallsBackTo500ForInvalidCode(): void
    {
        self::assertSame(500, ErrorService::normalizeCode(99));
    }

    #[Test]
    public function normalizeMessageKeepsSingleLineUtf8MessageUnchanged(): void
    {
        self::assertSame('Hello World', ErrorService::normalizeMessage('Hello World'));
    }

    #[Test]
    public function normalizeMessageReturnsOnlyFirstLineForNonUtf8Message(): void
    {
        self::assertSame('First', ErrorService::normalizeMessage("First\nSecond\xFF"));
    }

    #[Test]
    public function getReportLevelReturnsDefaultMaskExcludingNoisyLevelsWhenNoArgs(): void
    {
        $expected = E_ALL
            & ~E_WARNING
            & ~E_DEPRECATED
            & ~E_NOTICE
            & ~E_USER_WARNING
            & ~E_USER_DEPRECATED
            & ~E_USER_NOTICE;

        self::assertSame($expected, ErrorService::getReportLevel());
    }

    #[Test]
    public function getReportLevelBuildsMaskFromProvidedArray(): void
    {
        $level = ErrorService::getReportLevel(
            [
                E_ERROR => true,
                E_WARNING => true,
                E_NOTICE => false,
            ]
        );

        self::assertSame(E_ERROR | E_WARNING, $level);
    }
}
