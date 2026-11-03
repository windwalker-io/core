<?php

declare(strict_types=1);

namespace Windwalker\Core\Testing\Stub;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Test-only PSR-15 middleware that marks the request with a `passed` attribute and delegates.
 *
 * Shared, general-purpose PSR-15 double; safe to reuse from any test suite
 * exercising a middleware pipeline.
 */
class PassThroughMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request->withAttribute('passed', 'yes'));
    }
}
