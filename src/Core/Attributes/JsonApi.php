<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Attribute;
use Windwalker\Core\Middleware\AbstractMiddlewareAttribute;
use Windwalker\Core\Middleware\AttributeMiddlewareInterface;
use Windwalker\Core\Middleware\JsonApiMiddleware;
use Windwalker\DI\Container;

/**
 * The Json class.
 */
#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
class JsonApi extends AbstractMiddlewareAttribute
{
    public function createMiddleware(Container $container): AttributeMiddlewareInterface
    {
        return $container->newInstance(JsonApiMiddleware::class);
    }
}
