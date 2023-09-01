<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Middleware\WebSocket;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\Core\Application\MiddlewareRunner;
use Windwalker\Core\Application\WebSocket\WsAppContext;
use Windwalker\Core\Application\WebSocket\WsAppRequest;
use Windwalker\Core\Router\Exception\UnAllowedMethodException;
use Windwalker\Core\Router\Route;
use Windwalker\Core\Router\Router;
use Windwalker\Reactor\WebSocket\WebSocketRequest;
use Windwalker\Reactor\WebSocket\WebSocketRequestInterface;

/**
 * The WebSocketRoutingMiddleware class.
 */
class WebSocketRoutingMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected WsAppContext $app,
        protected Router $router
    ) {
        //
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $router = $this->router;

        if (!$request instanceof WebSocketRequestInterface) {
            throw new \InvalidArgumentException(static::class . ' must use on websocket environment.');
        }

        $request = $this->handleByClient($request);
        $route = $request->getUri()->getPath();

        $matched = $router->match($request, $route);

        $controller = $this->findController($matched);

        $container = $this->app->getContainer();

        $container->modify(
            WsAppContext::class,
            function (WsAppContext $context) use ($request, $controller, $matched) {
                $appRequest = $context->getAppRequest()
                    ->withRequest($request);

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

    protected function handleByClient(WebSocketRequestInterface $request): WebSocketRequestInterface
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
