<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Middleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Middleware\RoutingMiddleware;
use Windwalker\Core\Router\Exception\RouteNotFoundException;
use Windwalker\Core\Router\Exception\UnAllowedMethodException;
use Windwalker\Core\Router\Route;
use Windwalker\Core\Router\Router;
use Windwalker\Http\Response\Response;
use Windwalker\Tests\Core\Application\Stub\TestWebAppFactory;
use Windwalker\Tests\Core\Middleware\Stub\RouteMiddlewareStub;

class RoutingMiddlewareTest extends TestCase
{
    private function createContext(string $uri = 'http://example.com/foo', string $method = 'GET'): AppContext
    {
        return TestWebAppFactory::create()->createContext(TestWebAppFactory::request($uri, $method));
    }

    private function handler(): RequestHandlerInterface
    {
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturnCallback(
            static fn(ServerRequestInterface $request): ResponseInterface
                => new Response()->withHeader(
                    'X-Handled',
                    '1',
                ),
        );

        return $handler;
    }

    #[Test]
    public function processDispatchesToHandlerOnMatchedRoute(): void
    {
        $ctx = $this->createContext();
        $route = new Route('home', '/foo');
        $route->handlers(get: static fn() => 'ok');
        $router = new Router(['home' => $route]);

        $middleware = new RoutingMiddleware($ctx, $router);

        $result = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame('1', $result->getHeaderLine('X-Handled'));
        self::assertSame('home', $ctx->getMatchedRoute()?->getName());
        self::assertSame('ok', ($ctx->getController())());
    }

    #[Test]
    public function processThrowsWhenRouteNotFoundAndNoFallback(): void
    {
        $ctx = $this->createContext('http://example.com/missing');
        $router = new Router();

        $middleware = new RoutingMiddleware($ctx, $router);

        $this->expectException(RouteNotFoundException::class);

        $middleware->process($ctx->getServerRequest(), $this->handler());
    }

    #[Test]
    public function processUsesFallbackWhenRouteNotFound(): void
    {
        $ctx = $this->createContext('http://example.com/missing');
        $router = new Router();

        $fallbackRoute = new Route('fallback', '/missing');
        $fallbackRoute->handlers(get: static fn() => 'fallback-ok');

        $middleware = new RoutingMiddleware(
            $ctx,
            $router,
            fallback: static fn() => $fallbackRoute,
        );

        $result = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame('1', $result->getHeaderLine('X-Handled'));
        self::assertSame('fallback', $ctx->getMatchedRoute()?->getName());
    }

    #[Test]
    public function processRethrowsOriginalExceptionWhenFallbackAlsoFails(): void
    {
        $ctx = $this->createContext('http://example.com/missing');
        $router = new Router();

        $middleware = new RoutingMiddleware(
            $ctx,
            $router,
            fallback: static function (): never {
                throw new RouteNotFoundException('still missing');
            },
        );

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('still missing');

        $middleware->process($ctx->getServerRequest(), $this->handler());
    }

    #[Test]
    public function processUsesPreMatchResultWithoutCallingRouter(): void
    {
        $ctx = $this->createContext();
        $router = new Router();

        $preMatchedRoute = new Route('pre', '/foo');
        $preMatchedRoute->handlers(get: static fn() => 'pre-ok');

        $middleware = new RoutingMiddleware(
            $ctx,
            $router,
            preMatch: static fn() => $preMatchedRoute,
        );

        $result = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame('1', $result->getHeaderLine('X-Handled'));
        self::assertSame('pre', $ctx->getMatchedRoute()?->getName());
    }

    #[Test]
    public function processThrowsUnAllowedMethodExceptionForUnsupportedMethod(): void
    {
        $ctx = $this->createContext('http://example.com/foo', 'POST');
        $route = new Route('home', '/foo');
        $route->handlers(get: static fn() => 'ok');
        $router = new Router(['home' => $route]);

        $middleware = new RoutingMiddleware($ctx, $router);

        $this->expectException(UnAllowedMethodException::class);

        $middleware->process($ctx->getServerRequest(), $this->handler());
    }

    #[Test]
    public function processRunsRouteMiddlewaresBeforeHandler(): void
    {
        $ctx = $this->createContext();
        $route = new Route('home', '/foo');
        $route->handlers(get: static fn() => 'ok');
        $route->middlewares([RouteMiddlewareStub::class]);
        $router = new Router(['home' => $route]);

        $middleware = new RoutingMiddleware($ctx, $router);

        $result = $middleware->process($ctx->getServerRequest(), $this->handler());

        self::assertSame('yes', $result->getHeaderLine('X-Route-Middleware'));
        self::assertSame('1', $result->getHeaderLine('X-Handled'));
    }
}
