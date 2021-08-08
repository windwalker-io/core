<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\DI\DICreateTrait;

/**
 * The AbstractLifecycleMiddleware class.
 */
abstract class AbstractLifecycleMiddleware implements MiddlewareInterface
{
    use DICreateTrait;

    /**
     * prepareExecute
     *
     * @param  ServerRequestInterface  $request
     *
     * @return  mixed
     */
    abstract protected function preprocess(ServerRequestInterface $request);

    /**
     * postExecute
     *
     * @param  ResponseInterface  $response
     *
     * @return  mixed
     */
    abstract protected function postProcess(ResponseInterface $response);

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
        $r = $this->preprocess($request);

        $response = $handler->handle($r ?? $request);

        $r = $this->postProcess($response);

        return $r ?? $response;
    }
}
