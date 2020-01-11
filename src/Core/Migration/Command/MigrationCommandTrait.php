<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Migration\Command;

use Windwalker\Core\Database\DatabaseAdapter;
use Windwalker\Core\Database\DatabaseService;
use Windwalker\Core\Ioc;
use Windwalker\Core\Migration\Repository\BackupRepository;
use Windwalker\Core\Migration\Repository\MigrationsRepository;
use Windwalker\Database\Monitor\CallbackMonitor;
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
     * @param DatabaseAdapter $db
     *
     * @return  BackupRepository
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    public function getBackupRepository(DatabaseAdapter $db = null)
    {
        $db = $db ?: Ioc::getDatabase();

        return BackupRepository::getInstance()->setCommand($this)->setDb($db);
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
     * prepareLogger
     *
     * @return  void
     *
     * @since  3.5.16
     */
    protected function prepareLogger(): void
    {
        // DB log
        $this->console->database->setDebug(true);

        $this->console->database->setMonitor(
            new CallbackMonitor(function ($query) {
                $this->console->triggerEvent('onMigrationAfterQuery', ['query' => $query]);
            })
        );
    }

    /**
     * getEnvCmd
     *
     * @param string $env
     * @param string $value
     *
     * @return  string
     *
     * @since  3.5.3
     */
    public function getEnvCmd(string $env = 'APP_ENV', string $value = 'dev'): string
    {
        $prefix = $this->environment->getPlatform()->isWin()
            ? 'set'
            : 'export';

        return sprintf('%s %s=%s', $prefix, $env, $value);
    }
}
