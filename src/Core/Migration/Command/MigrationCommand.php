<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Migration\Command;

use Windwalker\Core\Console\CoreCommand;
use Windwalker\Core\Migration\Command\Migration;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Database\Driver\AbstractDatabaseDriver;
use Windwalker\Database\Middleware\DbProfilerMiddleware;

/**
 * The MigrationCommand class.
 *
 * @since  2.0
 */
class MigrationCommand extends CoreCommand
{
    /**
     * An enabled flag.
     *
     * @var bool
     */
    public static $isEnabled = true;

    /**
     * Console(Argument) name.
     *
     * @var  string
     */
    protected $name = 'migration';

    /**
     * The command description.
     *
     * @var  string
     */
    protected $description = 'Database migration system.';

    /**
     * The usage to tell user how to use this command.
     *
     * @var string
     */
    protected $usage = 'migration <cmd><command></cmd> <option>[option]</option>';

    /**
     * Configure command information.
     *
     * @return void
     */
    public function init()
    {
        $this->addCommand(new Migration\CreateCommand());
        $this->addCommand(new Migration\StatusCommand());
        $this->addCommand(new Migration\MigrateCommand());
        $this->addCommand(new Migration\ResetCommand());
        $this->addCommand(new Migration\DropAllCommand());

        $this->addGlobalOption('d')
            ->alias('dir')
            ->description('Set migration file directory.');

        $this->addGlobalOption('p')
            ->alias('package')
            ->description('Package to run migration.');
    }

    /**
     * prepareExecute
     *
     * @return  void
     * @throws \ReflectionException
     */
    protected function prepareExecute()
    {
        $config = $this->console->config;

        // Auto create database
        $name = $config['database.name'];

        $config['database.name'] = null;

        $db = $this->console->container->get(AbstractDatabaseDriver::class, true);

        $db->getDatabase($name)->create(true);

        $db->select($name);

        $config['database.name'] = $name;

        // Prepare migration path
        $packageName = $this->getOption('p');

        /** @var AbstractPackage $package */
        $package = $this->console->getPackage($packageName);

        if ($package) {
            $dir = $package->getDir() . '/Migration';
        } else {
            $dir = $this->getOption('d');
        }

        $dir = $dir ?: $this->console->get('path.migrations');

        $this->console->set('migration.dir', $dir);

        // DB log
        static $loggerRegistered = false;

        if (!$loggerRegistered) {
            $this->console->database->addMiddleware(new DbProfilerMiddleware(
                function () {
                    //
                },
                function (AbstractDatabaseDriver $db, \stdClass $data) {
                    $this->console->triggerEvent('onMigrationAfterQuery', ['query' => $db->getLastQuery()]);
                }
            ));

            $loggerRegistered = true;
        }
    }

    /**
     * Execute this command.
     *
     * @return int|void
     * @throws \ReflectionException
     */
    protected function doExecute()
    {
        $result = parent::doExecute();

        $this->console->database->resetMiddlewares();

        return $result;
    }
}
