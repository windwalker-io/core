<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Relay\Relay;
use Windwalker\DI\Container;

/**
 * The MiddlewareRunner class.
 */
class MiddlewareRunner
{
    /**
     * MiddlewareRunner constructor.
     *
     * @param  Container  $container
     */
    public function __construct(protected Container $container)
    {
    }

    public function run(ServerRequestInterface $request, array $middlewares, callable $handler): ResponseInterface
    {
        $middlewares = $this->compileMiddlewares($middlewares);
        $middlewares[] = $handler;

        return static::createRequestHandler($middlewares)->handle($request);
    }

    /**
     * compileMiddlewares
     *
     * @param  array      $middlewares
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    public function compileMiddlewares(array $middlewares): array
    {
        $queue = [];

        foreach ($middlewares as $middleware) {
            $queue[] = $this->container->resolve($middleware);
        }

        return $queue;
    }

    public static function createRequestHandler(iterable $queue): RequestHandlerInterface
    {
        return new Relay($queue);
    }
}
