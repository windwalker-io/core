<?php

declare(strict_types=1);

namespace Windwalker\WebSocket\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\Core\Application\MiddlewareRunner;
use Windwalker\Core\Router\Exception\UnAllowedMethodException;
use Windwalker\Core\Router\Route;
use Windwalker\Reactor\WebSocket\WebSocketRequest;
use Windwalker\Reactor\WebSocket\WebSocketRequestInterface;
use Windwalker\WebSocket\Application\WsAppContext;
use Windwalker\WebSocket\Router\WsRouter;

/**
 * The WebSocketRoutingMiddleware class.
 */
class WebSocketRoutingMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected WsAppContext $app,
        protected WsRouter $router
    ) {
        //
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $router = $this->router;

        if (!$request instanceof WebSocketRequestInterface) {
            throw new \InvalidArgumentException(static::class . ' must use on websocket environment.');
        }

        $request = $this->handleByParser($request);
        $route = $request->getRequestTarget();

        $matched = $router->match($request, $route);

        $controller = $this->findController($matched);

        $container = $this->app->getContainer();

        $container->modify(
            WsAppContext::class,
            function (WsAppContext $context) use ($request, $controller, $matched) {
                $appRequest = $context->getAppRequest()
                    ->withServerRequest($request)
                    ->withMatchedRoute($matched);

                return $context->setController($controller)
                    ->setMatchedRoute($matched)
                    ->setAppRequest($appRequest);
            }
        );

        $container->modify(WebSocketRequest::class, fn() => $request);

        $subscribers = $matched->getSubscribers();

        if ($subscribers !== []) {
            $this->app->handleListeners($subscribers, $container);
        }

        $middlewares = $matched->getMiddlewares();

        return $this->app->make(MiddlewareRunner::class)
            ->run(
                $request,
                $middlewares,
                fn(ServerRequestInterface $request) => $handler->handle($request)
            );
    }

    protected function handleByParser(WebSocketRequestInterface $request): WebSocketRequestInterface
    {
        return $this->app->getParser()->handleRequest($request);
    }

    protected function findController(Route $route): mixed
    {
        $handlers = $route->getHandlers();

        $handler = $handlers['*'] ?? null;

        if (!$handler) {
            throw new UnAllowedMethodException(
                sprintf(
                    'Controller not found for route: "%s".',
                    $route->getName()
                )
            );
        }

        return $handler;
    }
}
