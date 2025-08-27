<?php

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;
use Windwalker\DI\Container;

abstract class AbstractMiddlewareAttribute implements ContainerAttributeInterface
{
    abstract public function createMiddleware(Container $container): AttributeMiddlewareInterface;

    public function __invoke(AttributeHandler $handler): callable
    {
        return function (...$args) use ($handler) {
            $request = $handler->container->get(ServerRequestInterface::class);
            return $this->createMiddleware($handler->container)
                ->run($request, fn () => $handler(...$args));
        };
    }
}
