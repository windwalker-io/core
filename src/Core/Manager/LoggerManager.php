<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Psr\Log\LoggerInterface;

/**
 * The LoggerManager class.
 *
 * @method LoggerInterface create(?string $name = null, ...$args)
 * @method LoggerInterface get(?string $name = null, ...$args)
 */
class LoggerManager extends AbstractManager
{
    public function getConfigPrefix(): string
    {
        return 'logs';
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultFactory(string $name, ...$args): mixed
    {
        return $this->config->getDeep($this->getFactoryPath($this->getDefaultName()));
    }
}
