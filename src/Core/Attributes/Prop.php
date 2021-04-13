<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;

/**
 * The Prop class.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Prop implements ContainerAttributeInterface
{
    public function __invoke(AttributeHandler $handler): callable
    {
        return function () use ($handler) {
            $value = $handler();

            $container = $handler->getContainer();
            $self = $container->get('self');
            show($self);

            return $value;
        };
    }
}
