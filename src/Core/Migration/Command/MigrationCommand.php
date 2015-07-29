<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Migration\Command;

use Windwalker\Console\Command\Command;
use Windwalker\Core\Migration\Command\Migration;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Database\DatabaseFactory;
use Windwalker\Core\Ioc;

/**
 * The MigrationCommand class.
 * 
 * @since  2.0
 */
class MigrationCommand extends Command
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
	public function initialise()
	{
		$this->addCommand(new Migration\CreateCommand);
		$this->addCommand(new Migration\StatusCommand);
		$this->addCommand(new Migration\MigrateCommand);
		$this->addCommand(new Migration\ResetCommand);

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
	 */
	protected function prepareExecute()
	{
		$config = Ioc::getConfig();

		// Auto create database
		$name = $config['database.name'];

		$config['database.name'] = null;

		$db = Ioc::getDatabase();

		$db->getDatabase($name)->create(true);

		$db->select($name);

		$config['database.name'] = $name;

		// Prepare migration path
		$packageName = $this->getOption('p');

		/** @var AbstractPackage $package */
		$package = $this->app->getPackage($packageName);

		if ($package)
		{
			$dir = $package->getDir() . '/Migration';
		}
		else
		{
			$dir = $this->getOption('d');
		}

		$dir = $dir ? : $this->app->get('path.migrations');

		$this->app->set('migration.dir', $dir);
	}

	/**
	 * Execute this command.
	 *
	 * @return int|void
	 */
	protected function doExecute()
	{
		return parent::doExecute();
	}
}
 