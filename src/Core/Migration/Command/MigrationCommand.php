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
use Windwalker\Database\Monitor\CallbackMonitor;
use Windwalker\Database\Monitor\NullMonitor;

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
        $this->addCommand(Migration\CreateCommand::class);
        $this->addCommand(Migration\StatusCommand::class);
        $this->addCommand(Migration\MigrateCommand::class);
        $this->addCommand(Migration\ResetCommand::class);
        $this->addCommand(Migration\DropAllCommand::class);
        $this->addCommand(Migration\ExportCommand::class);
        $this->addCommand(Migration\EnableIndexesCommand::class);

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
        ini_set('max_execution_time', 0);

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
    }

    /**
     * Execute this command.
     *
     * @return int
     */
    protected function doExecute()
    {
        $result = parent::doExecute();

        $this->console->database->setMonitor(new NullMonitor());

        return $result;
    }
}
