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
use Windwalker\Structure\Structure;

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
     * @param array     $config
     * @param Structure $state
     *
     * @return  MigrationsRepository
     */
    public function getRepository($config = null, Structure $state = null)
    {
        $repository = (new MigrationsRepository($config, $state, $this->console->database))
            ->setCommand($this)
            ->setIo($this->io);

        $repository['path'] = $this->console->get('migration.dir') ?: $this->console->get('path.migrations');

        return $repository;
    }

    /**
     * getModel
     *
     * @param array     $config
     * @param Structure $state
     *
     * @return  BackupRepository
     */
    public function getBackupRepository($config = null, Structure $state = null)
    {
        return (new BackupRepository($config, $state, $this->console->database))->setCommand($this);
    }

    /**
     * backup
     *
     * @return  void
     */
    public function backup()
    {
        $this->getBackupRepository()->backup();
    }
}
