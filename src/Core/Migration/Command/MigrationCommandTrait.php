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
use Windwalker\Core\Ioc;
use Windwalker\Core\Migration\Repository\BackupRepository;
use Windwalker\Core\Migration\Repository\MigrationsRepository;
use Windwalker\DI\Annotation\Inject;
use Windwalker\DI\Container;
use Windwalker\Environment\Environment;

/**
 * The MigrationCommandTrait class.
 *
 * @since  3.2
 */
trait MigrationCommandTrait
{
    /**
     * Property environment.
     *
     * @Inject()
     *
     * @var Environment
     */
    protected $environment;

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

        $config['name'] = null;

        $db = $dbService->createConnection('_preset', $config->toArray());

        $db->getDatabase($name)->create(true);

        $db->select($name);

        $config['name'] = $name;
    }

    /**
     * getEnvCmd
     *
     * @param string $env
     * @param string $value
     *
     * @return  string
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getEnvCmd(string $env = 'WINDWALKER_MODE', string $value = 'dev'): string
    {
        $prefix = $this->environment->getPlatform()->isWin()
            ? 'setenv'
            : 'export';

        return sprintf('%s %s=%s', $prefix, $env, $value);
    }
}
