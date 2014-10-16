<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Migration\Command;

use Windwalker\Console\Command\Command;
use Windwalker\Core\Migration\Command\Migration;

/**
 * The MigrationCommand class.
 * 
 * @since  {DEPLOY_VERSION}
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

		$this->addGlobalOption('p')
			->alias('path')
			->description('Set migration file path.');
	}

	/**
	 * prepareExecute
	 *
	 * @return  void
	 */
	protected function prepareExecute()
	{
		$options = $this->getOptionSet(true);

		$options['p']->defaultValue($this->app->get('path.migrations'));
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
 