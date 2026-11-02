<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application\Stub;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Test-only PSR-15 middleware that marks the request with a `passed` attribute and delegates.
 *
 * Used by MiddlewareRunnerTest to verify class-name middleware resolution through the container.
 * Remove it together with those tests if MiddlewareRunner stops resolving class-name middleware.
 */
class PassThroughMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request->withAttribute('passed', 'yes'));
    }
}
