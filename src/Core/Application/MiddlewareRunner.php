<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

use Generator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\DI\Container;
use Windwalker\Http\Middleware\RequestRunner;
use Windwalker\Utilities\Wrapper\RawWrapper;

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

    public function run(
        ServerRequestInterface $request,
        iterable $middlewares,
        ?callable $last = null
    ): ResponseInterface {
        return $this->createRequestHandler(static::chainMiddlewares($middlewares, $last))->handle($request);
    }

    public static function chainMiddlewares(iterable $middlewares, ?callable $last = null): Generator
    {
        foreach ($middlewares as $middleware) {
            yield $middleware;
        }

        if ($last) {
            yield static fn () => $last;
        }
    }

    /**
     * compileMiddlewares
     *
     * @param  iterable       $middlewares
     * @param  callable|null  $last
     *
     * @return Generator
     */
    public function compileMiddlewares(iterable $middlewares, ?callable $last = null): Generator
    {
        foreach ($middlewares as $i => $middleware) {
            yield $this->resolveMiddleware($middleware);
        }

        if ($last) {
            yield $last;
        }
    }

    public function resolveMiddleware(mixed $middleware): mixed
    {
        if ($middleware === false || $middleware === null) {
            return null;
        }

        if ($middleware instanceof RawWrapper) {
            return $middleware();
        }

        if (is_callable($middleware)) {
            $middleware = $this->container->call($middleware);
        }

        if (is_callable($middleware)) {
            return $middleware;
        }

        return $this->container->resolve($middleware);
    }

    public function createRequestHandler(iterable $queue): RequestHandlerInterface
    {
        return new RequestRunner($queue, [$this, 'resolveMiddleware']);
    }
}
