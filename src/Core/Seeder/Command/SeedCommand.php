<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\Seeder\Command;

use Windwalker\Console\Command\Command;
use Windwalker\Console\Option\Option;
use Windwalker\Core\Seeder\Command\Seed\CleanCommand;
use Windwalker\Core\Seeder\Command\Seed\ImportCommand;

/**
 * Class Seed
 */
class SeedCommand extends Command
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
	protected $name = 'seed';

	/**
	 * The command description.
	 *
	 * @var  string
	 */
	protected $description = 'seed';

	/**
	 * The usage to tell user how to use this command.
	 *
	 * @var string
	 */
	protected $usage = 'Database seeder <cmd><command></cmd> <option>[option]</option>';

	/**
	 * Initialise command information.
	 *
	 * @return void
	 */
	public function initialise()
	{
		$this->addOption(
			new Option(array('c', 'class'), 'DatabaseSeeder', 'The class to import.', Option::IS_GLOBAL)
		);

		$this->addCommand(new ImportCommand);
		$this->addCommand(new CleanCommand);

		parent::initialise();
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
