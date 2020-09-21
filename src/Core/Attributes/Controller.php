<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;

/**
 * The Controller class.
 */
@@\Attribute
class Controller implements ContainerAttributeInterface
{
    /**
     * __invoke
     *
     * @param  AttributeHandler  $handler
     *
     * @return  callable
     */
    public function __invoke(AttributeHandler $handler): callable
    {

    }
}
