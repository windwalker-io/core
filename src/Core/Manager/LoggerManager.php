<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Windwalker\DI\Definition\DefinitionInterface;

/**
 * The LoggerManager class.
 */
class LoggerManager extends AbstractManager
{
    public function getConfigPrefix(): string
    {
        return 'logs';
    }
}
