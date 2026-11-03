<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Error;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Error\ErrorInlineHandler;

#[CoversClass(ErrorInlineHandler::class)]
class ErrorInlineHandlerTest extends TestCase
{
    #[Test]
    public function invokeEchoesMessageWhenAppIsInDebugMode(): void
    {
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('isDebug')->willReturn(true);

        $handler = new ErrorInlineHandler($app);

        $handler(new Exception('Boom'));

        self::assertSame('Boom', $this->getActualOutputForAssertion());
    }

    #[Test]
    public function invokeIsSilentWhenAppIsNotInDebugMode(): void
    {
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('isDebug')->willReturn(false);

        $handler = new ErrorInlineHandler($app);

        $handler(new Exception('Boom'));

        self::assertSame('', $this->getActualOutputForAssertion());
    }

    #[Test]
    public function invokeStopsWhenCallbackReturnsFalse(): void
    {
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('isDebug')->willReturn(true);

        $called = false;
        $handler = new ErrorInlineHandler(
            $app,
            callback: function ($e, $errorLevel) use (&$called) {
                $called = true;

                return false;
            }
        );

        $handler(new Exception('Boom'));

        self::assertTrue($called);
        self::assertSame('', $this->getActualOutputForAssertion());
    }

    #[Test]
    public function invokeContinuesWhenCallbackReturnsNonFalse(): void
    {
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('isDebug')->willReturn(true);

        $receivedException = null;
        $handler = new ErrorInlineHandler(
            $app,
            callback: function ($e, $errorLevel) use (&$receivedException) {
                $receivedException = $e;

                return null;
            }
        );

        $exception = new Exception('Boom');
        $handler($exception);

        self::assertSame($exception, $receivedException);
        self::assertSame('Boom', $this->getActualOutputForAssertion());
    }

    #[Test]
    public function invokeOverridesDisplayWithErrorLevelMatchingCurrentReporting(): void
    {
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('isDebug')->willReturn(false);

        $currentReporting = error_reporting();

        // A non-zero errorLevel that fully matches current reporting bits
        // must force display to true, even though isDebug() is false.
        $handler = new ErrorInlineHandler($app, errorLevel: $currentReporting);

        $handler(new Exception('Boom'));

        self::assertSame('Boom', $this->getActualOutputForAssertion());
    }

    #[Test]
    public function invokeOverridesDisplayWithErrorLevelNotMatchingCurrentReporting(): void
    {
        $app = $this->createStub(ApplicationInterface::class);
        $app->method('isDebug')->willReturn(true);

        // errorLevel of 0 can never match any bits in current error_reporting(),
        // so display must be forced to false even though isDebug() is true.
        $handler = new ErrorInlineHandler($app, errorLevel: 0);

        $handler(new Exception('Boom'));

        self::assertSame('', $this->getActualOutputForAssertion());
    }
}
