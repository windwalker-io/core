<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use FastRoute\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Router\Router;

/**
 * The RoutingMiddleware class.
 */
class RoutingMiddleware implements MiddlewareInterface
{
    protected WebApplication $app;

    /**
     * RoutingMiddleware constructor.
     *
     * @param  WebApplication  $app
     */
    public function __construct(WebApplication $app)
    {
        $this->app = $app;
    }

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     *
     * @param  ServerRequestInterface   $request
     * @param  RequestHandlerInterface  $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $router = $this->app->getContainer()->get(Router::class);
        $dispatcher = $router->getRouteDispatcher();
        $routeInfo = $dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new \RuntimeException('Page not found', 404);
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                throw new \RuntimeException('Method not allowed', 405);
                break;
            case Dispatcher::FOUND:
                [, $controller, $vars] = $routeInfo;
                $request = $request->withAttribute('controller', $controller);
                $request = $request->withAttribute('vars', $vars);

                break;
        }

        return $handler->handle($request);
    }
}
