<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;

use Windwalker\DI\Container;

use function Windwalker\ref;

/**
 * The Ref class.
 */
#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
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
        $container = $handler->getContainer();
        $ref = $handler->getReflector();
        $v = ref($this->path, $this->delimiter);

        return fn () => $container->getDependencyResolver()
            ->resolveParameterValue($v, $ref, Container::IGNORE_ATTRIBUTES);
    }
}
