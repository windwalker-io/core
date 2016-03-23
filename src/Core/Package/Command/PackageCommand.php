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

/**
 * The PackageCommand class.
 *
 * @since  {DEPLOY_VERSION}
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
	protected function initialise()
	{
		$this->addCommand(new CopyConfigCommand);

		$this->addGlobalOption('e')
			->alias('env')
			->description('The environment application name or class.')
			->defaultValue('Windwalker\Web\DevApplication');
	}
}
