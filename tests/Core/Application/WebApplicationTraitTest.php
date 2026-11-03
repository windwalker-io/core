<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\Context\AppRequestInterface;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Http\AppRequest;
use Windwalker\Core\Http\ProxyResolver;
use Windwalker\Core\Http\RequestInspector;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\RouteCreator;
use Windwalker\Core\Router\Router;
use Windwalker\Core\Router\RouteUri;
use Windwalker\Core\Router\SystemUri;
use Windwalker\DI\Container;
use Windwalker\Event\EventEmitter;
use Windwalker\Http\Output\Output;
use Windwalker\Http\Output\OutputInterface;
use Windwalker\Http\Request\ServerRequest;
use Windwalker\Http\Response\RedirectResponse;
use Windwalker\Http\Response\Response;
use Windwalker\Http\Response\TextResponse;
use Windwalker\Stream\Stream;
use Windwalker\Tests\Testing\Stub\SpyLogger;
use Windwalker\Tests\Core\Application\Stub\TestWebAppFactory;
use Windwalker\Uri\Uri;

use const Windwalker\Stream\WRITE_ONLY_RESET;

/**
 * Unit tests for the uncovered WebApplicationTrait branches: log()/getLogger(),
 * getNav()/navTo()/navBack(), respond(), and anyToResponse().
 */
class WebApplicationTraitTest extends TestCase
{
    private function registerNavigator(WebApplication $app): void
    {
        $container = $app->getContainer();
        $container->share(Router::class, new Router());
        $container->share(EventEmitter::class, new EventEmitter());
        $container->prepareSharedObject(Navigator::class);

        // Register a real route so that findRoute() resolves it directly without
        // needing the AppContext-dependent namespace fallback.
        $app->service(Router::class)->register(
            static function (RouteCreator $router): void {
                $router->any('some.route', '/some/route')->handler(static fn() => null);
            }
        );
    }

    /**
     * Builds a minimal request-scoped container with the pieces WebProvider would
     * normally wire up (ServerRequest, SystemUri, AppContext, Navigator) so that
     * resolving a route all the way to a RedirectResponse works without throwing
     * on the missing AppContext.
     */
    private function createRequestScopedNavigator(WebApplication $app): Navigator
    {
        $container = $app->getContainer()->createChild();

        $request = TestWebAppFactory::request();
        $container->share(ServerRequest::class, $request)
            ->alias(ServerRequestInterface::class, ServerRequest::class);
        $container->prepareSharedObject(ProxyResolver::class);
        $container->prepareSharedObject(RequestInspector::class);
        $container->share(
            SystemUri::class,
            static fn(Container $c) => $c->get(ProxyResolver::class)->handleProxyHost(
                SystemUri::parseFromRequest($c->get(ServerRequestInterface::class))
            )
        );

        $container->share(Router::class, new Router());
        $container->share(EventEmitter::class, new EventEmitter());
        $container->prepareSharedObject(AppContext::class);
        $container->set(
            AppRequest::class,
            static fn(Container $c) => $c->newInstance(AppRequest::class)
                ->withRequest($c->get(ServerRequestInterface::class))
                ->withSystemUri($c->get(SystemUri::class))
        )
            ->alias(AppRequestInterface::class, AppRequest::class);
        $container->prepareSharedObject(Navigator::class);

        $container->get(Router::class)->register(
            static function (RouteCreator $router): void {
                $router->any('some.route', '/some/route')->handler(static fn() => null);
            }
        );

        return $container->get(Navigator::class);
    }

    #[Test]
    public function logFallsBackToNullLoggerWhenNoneRegistered(): void
    {
        $app = TestWebAppFactory::create();

        // No LoggerInterface bound for this tag -> getLogger() returns a NullLogger.
        // Should not throw and should return $this for chaining.
        self::assertSame($app, $app->log('a message'));
    }

    #[Test]
    public function logUsesRegisteredLoggerForCurrentRuntimeTag(): void
    {
        $app = TestWebAppFactory::create();
        $logger = new SpyLogger();

        // Tests always run under the CLI SAPI, so isCliRuntime() is true and
        // getLogger() looks up the 'cli-web' tag.
        $app->getContainer()->set(LoggerInterface::class, $logger, tag: 'cli-web');

        $app->log('hello', ['foo' => 'bar'], 'warning');

        self::assertCount(1, $logger->records);
        self::assertSame('warning', $logger->records[0]['level']);
        self::assertSame('hello', $logger->records[0]['message']);
        self::assertSame(['foo' => 'bar'], $logger->records[0]['context']);
    }

    #[Test]
    public function getNavReturnsNavigatorFromContainer(): void
    {
        $app = TestWebAppFactory::create();
        $this->registerNavigator($app);

        self::assertInstanceOf(Navigator::class, $app->getNav());
    }

    #[Test]
    public function navToBuildsLazyRouteUriWithoutResolvingRoute(): void
    {
        $app = TestWebAppFactory::create();
        $this->registerNavigator($app);

        $uri = $app->navTo('some.route', ['id' => 1]);

        self::assertInstanceOf(RouteUri::class, $uri);
    }

    #[Test]
    public function respondSendsResponseThroughOutput(): void
    {
        $app = TestWebAppFactory::create();
        $stream = Stream::wrap('php://memory', WRITE_ONLY_RESET);
        $app->getContainer()->share(OutputInterface::class, new Output($stream));

        $app->respond(new TextResponse('hi'));

        // respond() returns void; reaching here without throwing proves the
        // OutputInterface was resolved and its respond() method invoked.
        self::addToAssertionCount(1);
    }

    #[Test]
    #[DataProvider('anyToResponseProvider')]
    public function anyToResponseConvertsVariousValues(mixed $input, string $expectedClass, string $expectedBody): void
    {
        $response = WebApplication::anyToResponse($input);

        self::assertInstanceOf($expectedClass, $response);

        if ($expectedBody !== '') {
            self::assertSame($expectedBody, (string) $response->getBody());
        }
    }

    public static function anyToResponseProvider(): iterable
    {
        yield 'already a response' => [new TextResponse('as-is'), Response::class, 'as-is'];
        yield 'plain uri redirects' => [new Uri('http://example.com/x'), RedirectResponse::class, ''];
        yield 'array becomes json' => [['a' => 1], Response::class, json_encode(['a' => 1], JSON_THROW_ON_ERROR)];
        yield 'scalar becomes string body' => [42, Response::class, '42'];
    }

    #[Test]
    public function anyToResponseConvertsRouteUriToRedirectResponse(): void
    {
        $app = TestWebAppFactory::create();
        $navigator = $this->createRequestScopedNavigator($app);

        $routeUri = $navigator->to('some.route');

        $response = WebApplication::anyToResponse($routeUri);

        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    #[Test]
    public function navBackBuildsRouteUriFromSystemRootWhenNoReferrer(): void
    {
        $app = TestWebAppFactory::create();
        $navigator = $this->createRequestScopedNavigator($app);
        $app->getContainer()->share(Navigator::class, $navigator);

        $uri = $app->navBack();

        self::assertInstanceOf(RouteUri::class, $uri);
    }
}
