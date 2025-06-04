<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes\Request;

use Windwalker\Core\Application\Context\AppRequestInterface;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
class RequestInput implements ContainerAttributeInterface
{
    public array $fields;

    public function __construct(...$fields)
    {
        $this->fields = $fields;
    }

    public function __invoke(AttributeHandler $handler): callable
    {
        return function () use ($handler) {
            return $handler()
                ?? $handler->getContainer()
                    ->get(AppRequestInterface::class)->input(...$this->fields);
        };
    }
}
