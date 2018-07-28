<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Migration\Command;

use Windwalker\Core\Migration\Repository\BackupRepository;
use Windwalker\Core\Migration\Repository\MigrationsRepository;

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
}
