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
use Windwalker\Core\Application\WebSocket\WsAppContext;
use Windwalker\Core\Router\Router;
use Windwalker\Reactor\WebSocket\WebSocketServerInterface;

/**
 * The SocketIORoutingMiddleware class.
 */
class SocketIORoutingMiddleware implements MiddlewareInterface
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

        if (!$request instanceof WebSocketServerInterface) {
            throw new \InvalidArgumentException(static::class . ' must use on websocket environment.');
        }

        $route = $this->getRoute($request);

        try {
            $matched = $router->match($request, $route);
        } catch (RouteNotFoundException $e) {
            $event = $this->emit(
                RoutingNotMatchedEvent::class,
                [
                    'route' => $route,
                    'request' => $request,
                    'systemUri' => $event->getSystemUri(),
                    'exception' => $e,
                ]
            );

            throw $event->getException();
        }

        $event = $this->emit(
            AfterRoutingEvent::class,
            [
                'route' => $route,
                'request' => $request,
                'systemUri' => $event->getSystemUri(),
                'matched' => $matched,
            ]
        );

        $controller = $this->findController($this->app->getRequestMethod(), $matched = $event->getMatched());

        $this->app->getContainer()->modify(
            AppContext::class,
            fn(AppContext $context) => $context->setController($controller)
                ->setMatchedRoute($matched)
        );

        $subscribers = $matched->getSubscribers();

        if ($subscribers !== []) {
            $this->app->handleListeners($subscribers, $this->app->getContainer());
        }

        $middlewares = $matched->getMiddlewares();
        $runner = $this->app->make(MiddlewareRunner::class);

        return $runner->run(
            $request,
            $middlewares,
            fn(ServerRequestInterface $request) => $handler->handle($request)
        );
    }

    protected function getRouteAndPayload(WebSocketServerInterface $request): array
    {
    }
}
