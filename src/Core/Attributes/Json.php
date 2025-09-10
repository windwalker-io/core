<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Attribute;
use Windwalker\Core\Middleware\JsonResponseMiddleware;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;

/**
 * The Json class.
 */
#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
class Json implements ContainerAttributeInterface
{
    public function __invoke(AttributeHandler $handler): callable
    {
        return static function (...$args) use ($handler) {
            $container = $handler->container;

            return $container->newInstance(JsonResponseMiddleware::class)->run(fn() => $handler(...$args));
        };
    }
}
