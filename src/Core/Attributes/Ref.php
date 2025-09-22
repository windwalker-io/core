<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Attribute;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;
use Windwalker\DI\Container;

use function Windwalker\ref;

/**
 * The Ref class.
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class Ref implements ContainerAttributeInterface
{
    /**
     * Ref constructor.
     *
     * @param  string       $path
     * @param  string|null  $delimiter
     */
    public function __construct(protected string $path, protected ?string $delimiter = '.')
    {
    }

    public function __invoke(AttributeHandler $handler): callable
    {
        $container = $handler->container;
        $v = ref($this->path, $this->delimiter);

        return static function () use ($handler, $container, $v) {
            $def = $handler();

            $value = $container->getDependencyResolver()
                ->resolveParameterValue($v) ?? $def;

            if ($handler->reflector instanceof \ReflectionProperty) {
                $handler->reflector->setValue($handler->object, $value);
            }

            return $value;
        };
    }
}
