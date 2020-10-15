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
use Windwalker\Core\Router\Exception\RouteNotFoundException;
use Windwalker\Core\Router\Exception\UnAllowedMethodException;
use Windwalker\Core\Router\Route;
use Windwalker\Core\Router\Router;
use Windwalker\DI\Exception\DefinitionException;

/**
 * The RoutingMiddleware class.
 */
class RoutingMiddleware implements MiddlewareInterface
{
    /**
     * RoutingMiddleware constructor.
     *
     * @param  WebApplication  $app
     */
    public function __construct(protected WebApplication $app)
    {
        //
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
     * @throws DefinitionException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $router = $this->app->service(Router::class);

        foreach ((array) $this->app->config('routing.routes') as $file) {
            $router->registerFile($file);
        }

        $route = $router->match($request);

        $action = $this->findController($request, $route);

        $request = $request->withAttribute('controller', $action);
        $request = $request->withAttribute('vars', $route->getVars());

        return $handler->handle($request);
    }

    /**
     * findAction
     *
     * @param  ServerRequestInterface         $request
     * @param  Route  $route
     *
     * @return  mixed
     */
    protected function findController(ServerRequestInterface $request, Route $route): mixed
    {
        $method = strtolower($request->getMethod());

        $handlers = $route->getHandlers();

        $handler = $handlers[$method] ?? $handlers['*'] ?? null;

        if (!$handler) {
            throw new UnAllowedMethodException(
                sprintf(
                    'Handler for method: "%s" not found in route: "%s".',
                    $method,
                    $route->getName()
                )
            );
        }

        return $handler;
    }
}
