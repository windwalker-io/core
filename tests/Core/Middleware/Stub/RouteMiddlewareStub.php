<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Middleware\Stub;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Minimal PSR-15 middleware used to verify RoutingMiddleware runs a matched
 * route's own middlewares before the final request handler.
 */
class RouteMiddlewareStub implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request)->withHeader('X-Route-Middleware', 'yes');
    }
}
