<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Migration\Command;

use Windwalker\Core\Database\DatabaseAdapter;
use Windwalker\Core\Database\DatabaseService;
use Windwalker\Core\Migration\Repository\BackupRepository;
use Windwalker\Core\Migration\Repository\MigrationsRepository;
use Windwalker\DI\Container;

/**
 * The MigrationCommandTrait class.
 *
 * @since  3.2
 */
trait MigrationCommandTrait
{
    /**
     * getModel
     *
     * @return  MigrationsRepository
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    public function getRepository()
    {
        $repository = MigrationsRepository::getInstance()
            ->setCommand($this)
            ->setIo($this->io);

        $repository['path'] = $this->console->get('migration.dir') ?: $this->console->get('path.migrations');

        return $repository;
    }

    /**
     * getModel
     *
     * @return  BackupRepository
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    public function getBackupRepository()
    {
        return BackupRepository::getInstance()->setCommand($this);
    }

    /**
     * backup
     *
     * @return  void
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    public function backup()
    {
        $this->getBackupRepository()->backup();
    }

    /**
     * createDatabase
     *
     * @return  void
     *
     * @since  3.4.6
     */
    protected function createDatabase()
    {
        /** @var Container $container */
        $container = $this->console->container;

        $dbService = $container->get(DatabaseService::class);
        $config    = $dbService->getDbConfig();

        // Auto create database
        $name = $config['name'];

        $config['database.name'] = null;

        $db = $container->get(DatabaseAdapter::class, true);

        $db->getDatabase($name)->create(true);

        $db->select($name);

        $config['database.name'] = $name;
    }
}
