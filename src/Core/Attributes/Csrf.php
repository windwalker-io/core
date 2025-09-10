<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Attribute;
use Psr\Http\Message\RequestInterface;
use Windwalker\Core\Middleware\CsrfMiddleware;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;

/**
 * The Csrf class.
 */
#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
class Csrf implements ContainerAttributeInterface
{
    public function __invoke(AttributeHandler $handler): callable
    {
        return static function (...$args) use ($handler) {
            $container = $handler->container;

            return $container->newInstance(CsrfMiddleware::class)
                ->run(
                    $container->get(RequestInterface::class),
                    fn() => $handler(...$args)
                );
        };
    }
}
