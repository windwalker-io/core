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
use Windwalker\Core\CliServer\CliServerRuntime;
use Windwalker\Core\Events\Web\TerminatingEvent;
use Windwalker\Core\Form\Exception\ValidateFailException;
use Windwalker\Core\Profiler\ProfilerFactory;
use Windwalker\Core\Router\Router;
use Windwalker\Core\Runtime\Config;
use Windwalker\Core\Security\Exception\InvalidTokenException;
use Windwalker\Event\EventEmitter;
use Windwalker\DI\Container;
use Windwalker\Core\Service\LoggerService;
use Windwalker\Http\Event\RequestEvent;
use Windwalker\Http\Output\Output;
use Windwalker\Http\Response\TextResponse;
use Windwalker\Http\Server\ServerInterface;
use Windwalker\Session\Bridge\PhpBridge;
use Windwalker\Session\Handler\ArrayHandler;
use Windwalker\Session\Session;
use Windwalker\Tests\Testing\Stub\FakeHttpServer;
use Windwalker\Tests\Core\Application\Stub\RecordingLoggerService;
use Windwalker\Tests\Core\Application\Stub\RequestLifecycleProviderStub;
use Windwalker\Tests\Core\Application\Stub\TestWebAppFactory;
use Windwalker\Tests\Core\Application\Stub\VerbosityAwareException;
use Windwalker\Tests\Core\Application\Stub\WebApplicationStub;

/**
 * Light integration tests: a real WebApplication boots and dispatches a controller.
 */
class WebApplicationTest extends TestCase
{
    #[Test]
    public function bootIsIdempotent(): void
    {
        $app = TestWebAppFactory::create();

        $app->boot();
        $container = $app->getContainer();
        $app->boot();

        self::assertSame($container, $app->getContainer());
    }

    #[Test]
    public function createContextBuildsAppContextFromRequest(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request('http://example.com/a?x=1'));

        self::assertInstanceOf(AppContext::class, $ctx);
        self::assertSame('http://example.com/a?x=1', (string) $ctx->getUri());
        self::assertSame('GET', $ctx->getRequestMethod());
        self::assertSame(['x' => '1'], $ctx->getQueryValues());
    }

    #[Test]
    public function runContextDispatchesControllerToResponse(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request());

        $res = $app->runContext($ctx, fn() => 'hello');

        self::assertSame(200, $res->getStatusCode());
        self::assertSame('hello', (string) $res->getBody());
    }

    #[Test]
    public function runContextInjectsContextIntoController(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request('http://example.com/a?x=1'));

        $res = $app->runContext($ctx, static fn(AppContext $app) => $app->input('x'));

        self::assertSame('1', (string) $res->getBody());
    }

    #[Test]
    public function middlewaresWrapControllerInRegistrationOrder(): void
    {
        $app = TestWebAppFactory::create();
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
            $app->createContext(TestWebAppFactory::request()),
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
        $app = TestWebAppFactory::create();
        $app->addMiddleware(fn() => fn() => new TextResponse('blocked', 403));

        $res = $app->runContext(
            $app->createContext(TestWebAppFactory::request()),
            fn() => self::fail('Controller must not run')
        );

        self::assertSame(403, $res->getStatusCode());
        self::assertSame('blocked', (string) $res->getBody());
    }

    #[Test]
    public function getRequestExceptionIsRethrown(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request(method: 'GET'));

        $this->expectException(RuntimeException::class);

        $app->runContext($ctx, function (): never {
            throw new RuntimeException('boom');
        });
    }

    #[Test]
    public function exceptionIsRethrownInDebugMode(): void
    {
        $app = TestWebAppFactory::create(['debug' => true]);
        $ctx = $app->createContext(TestWebAppFactory::request(method: 'POST'));

        $this->expectException(RuntimeException::class);

        $app->runContext($ctx, function (): never {
            throw new RuntimeException('boom');
        });
    }

    #[Test]
    public function executeRunsGivenHandlerAsController(): void
    {
        $app = TestWebAppFactory::create();

        $res = $app->execute(TestWebAppFactory::request(), fn() => 'from handler');

        self::assertSame('from handler', (string) $res->getBody());
    }

    #[Test]
    public function configAccessorsReadAppConfig(): void
    {
        $app = TestWebAppFactory::create(['name' => 'Foo', 'debug' => true]);

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
        $app = TestWebAppFactory::create(['debug' => $debug, 'verbosity' => $verbosity]);

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
        $app = TestWebAppFactory::create(['secret' => 'abc']);

        $derived = $app->getSecret('cookie');

        self::assertSame('abc', $app->getSecret());
        self::assertSame(32, strlen($derived));
        self::assertSame($derived, $app->getSecret('cookie'));
        self::assertNotSame($derived, $app->getSecret('csrf'));
    }

    #[Test]
    public function debugProfilerCanBeToggled(): void
    {
        $app = TestWebAppFactory::create();

        self::assertFalse($app->isDebugProfilerDisabled());

        $app->disableDebugProfiler();

        self::assertTrue($app->isDebugProfilerDisabled());
    }

    #[Test]
    public function bootStartsProfilerWhenAppTypeIsWeb(): void
    {
        // isCliRuntime() = false makes getType() resolve to AppType::WEB instead of CLI_WEB.
        $app = $this->createStubApp(cliRuntime: false);
        $app->loadConfig(['middlewares' => [fn () => null]]);

        $app->boot();

        self::assertInstanceOf(ProfilerFactory::class, $app->getContainer()->get(ProfilerFactory::class));
        self::assertCount(1, $app->getMiddlewares());
    }

    #[Test]
    public function stopContextEmitsAfterRespondEventAndReleasesRequestProviders(): void
    {
        RequestLifecycleProviderStub::reset();

        $app = TestWebAppFactory::create();
        $app->loadConfig(['di' => ['providers' => [RequestLifecycleProviderStub::class]]]);

        $ctx = $app->createContext(TestWebAppFactory::request());

        self::assertSame(1, RequestLifecycleProviderStub::$bootCount);
        self::assertSame(0, RequestLifecycleProviderStub::$releaseCount);

        $app->stopContext($ctx);

        self::assertSame(1, RequestLifecycleProviderStub::$releaseCount);
    }

    #[Test]
    public function terminateEmitsTerminatingEventWithAppAndContainer(): void
    {
        $app = TestWebAppFactory::create();

        $received = null;
        $app->on(TerminatingEvent::class, function (TerminatingEvent $event) use (&$received) {
            $received = $event;
        });

        $app->terminate();

        self::assertInstanceOf(TerminatingEvent::class, $received);
        self::assertSame($app, $received->app);
        self::assertSame($app->getContainer(), $received->container);
    }

    #[Test]
    public function addMessageAndGetMessagesAreNoOpStubs(): void
    {
        $app = TestWebAppFactory::create();

        self::assertSame($app, $app->addMessage('hello'));
        self::assertSame([], $app->getMessages());
    }

    #[Test]
    public function redirectReturnsRedirectResponseWhenHeadersNotSent(): void
    {
        $app = TestWebAppFactory::create();

        $res = $app->redirect('http://example.com/target');

        self::assertSame(303, $res->getStatusCode());
        self::assertSame('http://example.com/target', $res->getHeaderLine('Location'));
    }

    #[Test]
    public function redirectUsesJavascriptResponseWhenHeadersAlreadySent(): void
    {
        $app = $this->createStubApp();
        $app->headersSentOverride = true;

        $res = $app->redirect('http://example.com/target');

        self::assertSame(200, $res->getStatusCode());
        self::assertStringContainsString(
            "document.location.href='http://example.com/target'",
            (string) $res->getBody()
        );
    }

    #[Test]
    public function redirectInstantRespondsThenClosesApplication(): void
    {
        $app = $this->createStubApp();

        ob_start();
        $res = $app->redirect('http://example.com/target', instant: true);
        ob_end_clean();

        self::assertSame(303, $res->getStatusCode());
        self::assertTrue($app->closeCalled);
        self::assertSame('', $app->closeReturn);
    }

    #[Test]
    public function dispatchRedirectsBackOnValidateFailException(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(TestWebAppFactory::request(method: 'POST'));
        $ctx->getContainer()->share(Router::class, new Router());
        $ctx->getContainer()->share(EventEmitter::class, new EventEmitter());
        $this->shareDisabledSession($ctx);

        $res = $app->runContext($ctx, function (): never {
            throw new ValidateFailException('invalid');
        });

        self::assertSame(303, $res->getStatusCode());
    }

    #[Test]
    public function dispatchRethrowsValidateFailExceptionForApiCall(): void
    {
        $app = TestWebAppFactory::create();
        $ctx = $app->createContext(
            TestWebAppFactory::request(method: 'POST', headers: ['Content-Type' => 'application/json'])
        );

        $this->expectException(ValidateFailException::class);

        $app->runContext($ctx, function (): never {
            throw new ValidateFailException('invalid');
        });
    }

    #[Test]
    public function dispatchRethrowsInvalidTokenExceptionWhenDebugVeryVerbose(): void
    {
        $app = TestWebAppFactory::create(['debug' => true, 'verbosity' => AppVerbosity::VERY_VERBOSE->value]);
        $ctx = $app->createContext(TestWebAppFactory::request(method: 'POST'));

        $this->expectException(InvalidTokenException::class);

        $app->runContext($ctx, function (): never {
            throw new InvalidTokenException('bad token');
        });
    }

    #[Test]
    public function dispatchLogsAndRedirectsOnUnhandledThrowable(): void
    {
        $app = TestWebAppFactory::create();
        $logger = new RecordingLoggerService();
        $app->getContainer()->share(LoggerService::class, $logger);

        $ctx = $app->createContext(TestWebAppFactory::request(method: 'POST'));
        $ctx->getContainer()->share(Router::class, new Router());
        $ctx->getContainer()->share(EventEmitter::class, new EventEmitter());
        $this->shareDisabledSession($ctx);

        $res = $app->runContext($ctx, function (): never {
            throw new RuntimeException('boom', 500);
        });

        self::assertSame(303, $res->getStatusCode());
        self::assertCount(1, $logger->errors);
        self::assertSame('error', $logger->errors[0]['channel']);
        self::assertSame('boom', $logger->errors[0]['message']);
    }

    #[Test]
    public function logErrorSkipsLoggingFor40xErrors(): void
    {
        $app = TestWebAppFactory::create();
        $logger = new RecordingLoggerService();
        $app->getContainer()->share(LoggerService::class, $logger);

        $ctx = $app->createContext(TestWebAppFactory::request(method: 'POST'));
        $ctx->getContainer()->share(Router::class, new Router());
        $ctx->getContainer()->share(EventEmitter::class, new EventEmitter());
        $this->shareDisabledSession($ctx);

        $res = $app->runContext($ctx, function (): never {
            throw new RuntimeException('not found', 404);
        });

        self::assertSame(303, $res->getStatusCode());
        self::assertSame([], $logger->errors);
    }

    #[Test]
    public function logErrorUsesVerbosityMessageForVerbosityAwareExceptions(): void
    {
        $app = TestWebAppFactory::create();
        $logger = new RecordingLoggerService();
        $app->getContainer()->share(LoggerService::class, $logger);

        $ctx = $app->createContext(TestWebAppFactory::request(method: 'POST'));
        $ctx->getContainer()->share(Router::class, new Router());
        $ctx->getContainer()->share(EventEmitter::class, new EventEmitter());
        $this->shareDisabledSession($ctx);

        $app->runContext($ctx, function (): never {
            throw new VerbosityAwareException('oops', 500);
        });

        self::assertCount(1, $logger->errors);
        self::assertSame('verbose: oops', $logger->errors[0]['message']);
    }

    #[Test]
    public function bootForServerSharesServerAndBoots(): void
    {
        $app = TestWebAppFactory::create();
        $server = new FakeHttpServer();

        $app->bootForServer($server);

        self::assertSame($server, $app->getContainer()->get(ServerInterface::class));
        self::assertSame($server, $app->getContainer()->get(FakeHttpServer::class));
    }

    #[Test]
    public function createContextFromServerEventSharesOutputAndSetsAttribute(): void
    {
        $app = TestWebAppFactory::create();
        $output = new Output('php://memory');
        $event = new RequestEvent(request: TestWebAppFactory::request(), output: $output);

        $appContext = $app->createContextFromServerEvent($event);

        self::assertInstanceOf(AppContext::class, $appContext);
        self::assertSame($output, $appContext->getContainer()->get(\Windwalker\Http\Output\OutputInterface::class));
        self::assertSame($appContext, $event->attributes['appContext']);
    }

    #[Test]
    public function executeServerEventRunsContextAndSetsEndHandler(): void
    {
        $app = TestWebAppFactory::create();
        $app->boot();
        $app->on(
            \Windwalker\Core\Events\Web\BeforeRequestEvent::class,
            fn ($event) => $event->container->get(AppContext::class)->setController(fn () => 'ok')
        );
        $event = new RequestEvent(request: TestWebAppFactory::request(), output: new Output('php://memory'));

        $res = $app->executeServerEvent($event);

        self::assertSame(200, $res->getStatusCode());
        self::assertNotNull($event->endHandler);
    }

    #[Test]
    public function runCliServerRequestLogsSuccessfulRequestAndSetsResponse(): void
    {
        $app = TestWebAppFactory::create();
        $app->boot();
        $app->on(
            \Windwalker\Core\Events\Web\BeforeRequestEvent::class,
            fn ($event) => $event->container->get(AppContext::class)->setController(fn () => 'ok')
        );
        $event = new RequestEvent(request: TestWebAppFactory::request(), output: new Output('php://memory'));

        ob_start();
        $result = $app->runCliServerRequest($event);
        ob_end_clean();

        self::assertSame($event, $result);
        self::assertSame(200, $event->response->getStatusCode());
        self::assertNotNull($event->endHandler);
    }

    #[Test]
    public function runCliServerRequestLogsAndHandlesUnhandledServerError(): void
    {
        $app = TestWebAppFactory::create(['debug' => true, 'verbosity' => AppVerbosity::VERY_VERBOSE->value]);
        $app->boot();
        $event = new RequestEvent(request: TestWebAppFactory::request(method: 'POST'), output: new Output('php://memory'));

        // dispatch() re-throws InvalidTokenException as-is when debug + very-verbose,
        // so runCliServerRequest's own catch branch (non-client-error path) runs.
        CliServerRuntime::getOutput()->setVerbosity(\Symfony\Component\Console\Output\OutputInterface::VERBOSITY_QUIET);
        $app->getContainer()->share(
            RequestHandlerInterface::class,
            new class () implements RequestHandlerInterface {
                public function handle(ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
                {
                    throw new InvalidTokenException('bad token', 500);
                }
            }
        );

        ob_start();
        $result = $app->runCliServerRequest($event);
        ob_end_clean();

        self::assertSame($event, $result);
        self::assertNull($event->response);
        self::assertNotNull($event->endHandler);
    }

    private function shareDisabledSession(AppContext $ctx): void
    {
        $ctx->getContainer()->share(Session::class, new Session([], new PhpBridge(handler: new ArrayHandler())));
    }

    private function createStubApp(array $config = [], bool $cliRuntime = true): WebApplicationStub
    {
        $params = new Config();
        $container = new Container()->setParameters($params);
        $container->share(Config::class, $params);

        $app = new WebApplicationStub($container, $cliRuntime);
        $app->loadConfig(['app' => $config + ['name' => 'TestApp', 'debug' => false, 'secret' => 'test-secret']]);

        return $app;
    }
}
