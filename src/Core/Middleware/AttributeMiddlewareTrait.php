<?php

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Controller\ControllerDispatcher;

/**
 * Trait AttributeMiddlewareTrait
 */
trait AttributeMiddlewareTrait
{
    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return AppContext::anyToResponse(
            $this->run(
                $request,
                fn(ServerRequestInterface $request): ResponseInterface => $handler->handle($request)
            )
        );
    }

    abstract public function run(ServerRequestInterface $request, Closure $next): mixed;
}
