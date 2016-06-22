<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Asset\Command;

use Windwalker\Core\Asset\Command\Asset\MakesumCommand;
use Windwalker\Core\Asset\Command\Asset\SyncCommand;
use Windwalker\Console\Command\Command;

/**
 * The AssetCommand class.
 * 
 * @since  2.1.1
 */
class AssetCommand extends Command
{
	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name = 'asset';

	/**
	 * Property description.
	 *
	 * @var  string
	 */
	protected $description = 'Asset management';

	/**
	 * initialise
	 *
	 * @return  void
	 */
	protected function init()
	{
		$this->addCommand(new SyncCommand);
		$this->addCommand(new MakesumCommand);

		$this->addGlobalOption('e')
			->alias('env')
			->description('The environment application name or class.')
			->defaultValue('dev');
	}
}
