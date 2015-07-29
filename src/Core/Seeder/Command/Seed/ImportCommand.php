<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT Taiwan, Inc. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Seeder\Command\Seed;

use Windwalker\Console\Command\Command;
use Windwalker\Core\Ioc;
use Windwalker\Core\Migration\Model\BackupModel;
use Windwalker\Core\Package\PackageHelper;
use Windwalker\Core\Utilities\Classes\MvcHelper;
use Windwalker\String\StringNormalise;

/**
 * Class Seed
 */
class ImportCommand extends Command
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
	protected $name = 'import';

	/**
	 * The command description.
	 *
	 * @var  string
	 */
	protected $description = 'Import seed';

	/**
	 * The usage to tell user how to use this command.
	 *
	 * @var string
	 */
	protected $usage = 'import <cmd><command></cmd> <option>[option]</option>';

	/**
	 * Initialise command information.
	 *
	 * @return void
	 */
	public function initialise()
	{
		parent::initialise();
	}

	/**
	 * Execute this command.
	 *
	 * @return int|void
	 */
	protected function doExecute()
	{
		if (!$this->io->getOption('no-backup'))
		{
			// backup
			BackupModel::getInstance()->setCommand($this)->backup();
		}

		$class = $this->app->get('seed.class');

		/** @var \Windwalker\Core\Seeder\AbstractSeeder $seeder */
		$seeder = new $class(Ioc::getDatabase(), $this);

		$seeder->doExecute();

		return true;
	}
}
