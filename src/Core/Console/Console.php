<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Console;

use Windwalker\Core\Migration\Command\PhinxCommand;
use Windwalker\Core\Seeder\Command\SeedCommand;
use Windwalker\Windwalker;

/**
 * The WindwalkerConsole class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class Console extends WindwalkerConsole
{
	/**
	 * initialise
	 *
	 * @return  void
	 */
	protected function initialise()
	{
		Windwalker::prepareSystemPath($this->config);

		parent::initialise();

		$this->addCommand(new PhinxCommand);
		$this->addCommand(new SeedCommand);
	}
}
 