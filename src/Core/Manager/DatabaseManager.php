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
use Windwalker\Database\DatabaseFactory;
use Windwalker\ORM\ORM;

/**
 * The DatabaseManager class.
 *
 * @method DatabaseAdapter get(?string $name = null, ...$args)
 */
class DatabaseManager extends AbstractManager
{
    public function getConfigPrefix(): string
    {
        return 'database';
    }

    protected function getFactoryPath(string $name): string
    {
        return 'connections.' . $name;
    }

    /**
     * create
     *
     * @param  string|null  $name
     * @param  mixed        ...$args
     *
     * @return  DatabaseAdapter
     */
    public function create(?string $name = null, ...$args): object
    {
        /** @var DatabaseAdapter $db */
        $db = parent::create($name, ...$args);

        $db->orm()->setAttributesResolver($this->container->getAttributesResolver());

        return $db;
    }

    public function getORM(?string $name = null): ORM
    {
        return $this->get($name)->orm();
    }

    public function getDatabaseFactory(): DatabaseFactory
    {
        return $this->container->resolve(DatabaseFactory::class);
    }
}
