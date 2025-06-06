<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Windwalker\Core\Manager\DatabaseManager;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;

#[\Attribute]
class Transaction implements ContainerAttributeInterface
{
    public function __construct(public ?string $connection = null, public bool $enabled = true)
    {
    }

    public function __invoke(AttributeHandler $handler): callable
    {
        return function (...$args) use ($handler) {
            $container = $handler->getContainer();

            return $container->get(DatabaseAdapter::class, tag: $this->connection)
                ->transaction(fn () => $handler(...$args), true, $this->enabled);
        };
    }
}
