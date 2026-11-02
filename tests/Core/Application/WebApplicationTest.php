<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\AppVerbosity;
use Windwalker\Http\Response\TextResponse;
use Windwalker\Tests\Core\Application\Stub\WebApplicationFactory;

/**
 * Light integration tests: a real WebApplication boots and dispatches a controller.
 */
class WebApplicationTest extends TestCase
{
    #[Test]
    public function bootIsIdempotent(): void
    {
        $app = WebApplicationFactory::create();

        $app->boot();
        $container = $app->getContainer();
        $app->boot();

        self::assertSame($container, $app->getContainer());
    }

    #[Test]
    public function createContextBuildsAppContextFromRequest(): void
    {
        $app = WebApplicationFactory::create();
        $ctx = $app->createContext(WebApplicationFactory::request('http://example.com/a?x=1'));

        self::assertInstanceOf(AppContext::class, $ctx);
        self::assertSame('http://example.com/a?x=1', (string) $ctx->getUri());
        self::assertSame('GET', $ctx->getRequestMethod());
        self::assertSame(['x' => '1'], $ctx->getQueryValues());
    }

    #[Test]
    public function runContextDispatchesControllerToResponse(): void
    {
        $app = WebApplicationFactory::create();
        $ctx = $app->createContext(WebApplicationFactory::request());

        $res = $app->runContext($ctx, fn() => 'hello');

        self::assertSame(200, $res->getStatusCode());
        self::assertSame('hello', (string) $res->getBody());
    }

    #[Test]
    public function runContextInjectsContextIntoController(): void
    {
        $app = WebApplicationFactory::create();
        $ctx = $app->createContext(WebApplicationFactory::request('http://example.com/a?x=1'));

        $res = $app->runContext($ctx, static fn(AppContext $app) => $app->input('x'));

        self::assertSame('1', (string) $res->getBody());
    }

    #[Test]
    public function middlewaresWrapControllerInRegistrationOrder(): void
    {
        $app = WebApplicationFactory::create();
        $log = [];

        foreach (['A', 'B'] as $name) {
            // A closure middleware is a factory: the container calls it to get the real middleware.
            $app->addMiddleware(
                static function () use ($name, &$log) {
                    return static function (
                        ServerRequestInterface $req,
                        RequestHandlerInterface $next
                    ) use ($name, &$log) {
                        $log[] = "in:$name";
                        $res = $next->handle($req);
                        $log[] = "out:$name";

                        return $res;
                    };
                }
            );
        }

        $app->runContext(
            $app->createContext(WebApplicationFactory::request()),
            function () use (&$log) {
                $log[] = 'controller';

                return 'ok';
            }
        );

        self::assertSame(['in:A', 'in:B', 'controller', 'out:B', 'out:A'], $log);
    }

    #[Test]
    public function middlewareCanShortCircuitWithoutReachingController(): void
    {
        $app = WebApplicationFactory::create();
        $app->addMiddleware(fn() => fn() => new TextResponse('blocked', 403));

        $res = $app->runContext(
            $app->createContext(WebApplicationFactory::request()),
            fn() => self::fail('Controller must not run')
        );

        self::assertSame(403, $res->getStatusCode());
        self::assertSame('blocked', (string) $res->getBody());
    }

    #[Test]
    public function getRequestExceptionIsRethrown(): void
    {
        $app = WebApplicationFactory::create();
        $ctx = $app->createContext(WebApplicationFactory::request(method: 'GET'));

        $this->expectException(RuntimeException::class);

        $app->runContext($ctx, function (): never {
            throw new RuntimeException('boom');
        });
    }

    #[Test]
    public function exceptionIsRethrownInDebugMode(): void
    {
        $app = WebApplicationFactory::create(['debug' => true]);
        $ctx = $app->createContext(WebApplicationFactory::request(method: 'POST'));

        $this->expectException(RuntimeException::class);

        $app->runContext($ctx, function (): never {
            throw new RuntimeException('boom');
        });
    }

    #[Test]
    public function configAccessorsReadAppConfig(): void
    {
        $app = WebApplicationFactory::create(['name' => 'Foo', 'debug' => true]);

        self::assertSame('Foo', $app->getAppName());
        self::assertTrue($app->isDebug());
        self::assertSame('Foo', $app->config('app.name'));
    }

    /**
     * @param  mixed  $verbosity
     */
    #[Test]
    #[DataProvider('verbosityProvider')]
    public function getVerbosityResolvesFromDebugAndConfig(
        bool $debug,
        mixed $verbosity,
        AppVerbosity $expected
    ): void {
        $app = WebApplicationFactory::create(['debug' => $debug, 'verbosity' => $verbosity]);

        self::assertSame($expected, $app->getVerbosity());
    }

    public static function verbosityProvider(): array
    {
        return [
            'unset, no debug' => [false, null, AppVerbosity::NORMAL],
            'unset, debug' => [true, null, AppVerbosity::VERBOSE],
            'empty string, debug' => [true, '', AppVerbosity::VERBOSE],
            'explicit very verbose' => [false, AppVerbosity::VERY_VERBOSE->value, AppVerbosity::VERY_VERBOSE],
            'debug raises low verbosity' => [true, AppVerbosity::NORMAL->value, AppVerbosity::VERBOSE],
            'invalid value falls back' => [false, 999, AppVerbosity::NORMAL],
        ];
    }

    #[Test]
    public function getSecretReturnsRawAndDerivedKeys(): void
    {
        $app = WebApplicationFactory::create(['secret' => 'abc']);

        $derived = $app->getSecret('cookie');

        self::assertSame('abc', $app->getSecret());
        self::assertSame(32, strlen($derived));
        self::assertSame($derived, $app->getSecret('cookie'));
        self::assertNotSame($derived, $app->getSecret('csrf'));
    }

    #[Test]
    public function debugProfilerCanBeToggled(): void
    {
        $app = WebApplicationFactory::create();

        self::assertFalse($app->isDebugProfilerDisabled());

        $app->disableDebugProfiler();

        self::assertTrue($app->isDebugProfilerDisabled());
    }
}
