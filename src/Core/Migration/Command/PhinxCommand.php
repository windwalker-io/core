<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2011 - 2014 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Windwalker\Core\Migration\Command;

use Windwalker\Console\Command\Command;
use Windwalker\Ioc;

/**
 * Class Migration
 */
class PhinxCommand extends Command
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
	protected $name = 'phinx';

	/**
	 * The command description.
	 *
	 * @var  string
	 */
	protected $description = 'Migration system by Phinx';

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
		// $this->addArgument();

		parent::initialise();
	}

	/**
	 * prepareExecute
	 *
	 * @return  void
	 */
	protected function prepareExecute()
	{
		$this->app->set('show_help', false);
	}

	/**
	 * Execute this command.
	 *
	 * @return int|void
	 */
	protected function doExecute()
	{
		$argv = $_SERVER['argv'];

		array_shift($argv);
		array_shift($argv);

		$server = Ioc::getEnvironment()->server;

		if ($argv >= 2)
		{
			$argv[] = '--configuration=' . realpath(dirname($server->getEntry()) . '/../etc/phinx.config.php') . '';
		}

		array_unshift($argv, 'phinx');

		$_SERVER['argv'] = $argv;

		include Ioc::getConfig()->get('path.vendor') . '/robmorgan/phinx/bin/phinx';
	}
}
