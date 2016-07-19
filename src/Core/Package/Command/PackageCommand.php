<?php
/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Package\Command;

use Windwalker\Console\Command\Command;
use Windwalker\Core\Package\Command\Package\CopyConfigCommand;
use Windwalker\Core\Package\Command\Package\InstallCommand;

/**
 * The PackageCommand class.
 *
 * @since  3.0
 */
class PackageCommand extends Command
{
	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name = 'package';

	/**
	 * Property description.
	 *
	 * @var  string
	 */
	protected $description = 'Package operations.';

	/**
	 * Initialise command.
	 *
	 * @return void
	 *
	 * @since  2.0
	 */
	protected function init()
	{
		$this->addCommand(CopyConfigCommand::class);
		$this->addCommand(InstallCommand::class);

		$this->addGlobalOption('e')
			->alias('env')
			->description('The environment application name or class.')
			->defaultValue('dev');
	}
}
