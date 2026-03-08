<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Windwalker\Data\Collection;
use Windwalker\Data\RecordInterface;
use Windwalker\Database\Hydrator\SimpleHydrator;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;
use Windwalker\ORM\Metadata\EntityMetadata;
use Windwalker\ORM\ORM;
use Windwalker\Utilities\Enum\EnumExtendedInterface;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class MapTo implements ContainerAttributeInterface
{
    public static array $mapHandlers = [];

    public function __construct(
        public string|object $className,
        public bool $try = false,
        public bool $nullable = true,
    ) {
    }

    public function __invoke(AttributeHandler $handler): callable
    {
        return function () use ($handler) {
            $value = $handler();

            if ($value === null && $this->nullable) {
                return null;
            }

            return $this->mapValue($value, $handler);
        };
    }

    protected function mapValue(mixed $value, AttributeHandler $handler): mixed
    {
        $className = $this->className;

        // Enum
        if (is_string($className) && enum_exists($className)) {
            if (is_a($className, EnumExtendedInterface::class, true)) {
                return $this->try ? $className::tryWrap($value) : $className::wrap($value);
            }

            return $this->try ? $className::tryFrom($value) : $className::from($value);
        }

        // Record
        if (is_string($className) && is_a($className, RecordInterface::class, true)) {
            return $this->try
                ? $className::tryWrapWith(...$value)
                : $className::wrapWith(...$value);
        }

        if (is_object($className) && $className instanceof RecordInterface) {
            if ($this->try && !is_array($value)) {
                return null;
            }

            return $className->with(...$value);
        }

        // Entity
        if (EntityMetadata::isEntity($className)) {
            if (
                $this->try
                && !is_iterable($value)
            ) {
                return null;
            }

            $orm = $handler->container->get(ORM::class);

            if (is_string($className)) {
                $className = $orm->createEntity($className);
            }

            return $orm->hydrateEntity($value, $className);
        }

        // Custom map handler
        foreach (static::$mapHandlers as $mapHandler) {
            $result = $mapHandler($this, $value, $handler);

            if ($result !== null) {
                return $result;
            }
        }

        if ($this->try && !is_array($value)) {
            return null;
        }

        if (is_string($className)) {
            $className = new $className();
        }

        return new SimpleHydrator()->hydrate($className, $value);
    }
}
