<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Windwalker\Database\DatabaseAdapter;

/**
 * The DatabaseManager class.
 *
 * @method DatabaseAdapter get(?string $name = null, ...$args)
 * @method DatabaseAdapter create(?string $name = null, ...$args)
 */
class DatabaseManager extends AbstractManager
{
    public function getConfigPrefix(): string
    {
        return 'database';
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultFactory(string $name, ...$args)
    {
        return $this->config->getDeep($this->getFactoryPath($this->getDefaultName()));
    }
}
