<?php

namespace Windwalker\SystemPackage\Command;

use Windwalker\Console\Command\Command;
use Windwalker\SystemPackage\Command\Build\GenerateCommand;

/**
 * Class BuildCommand
 *
 * @since 1.0
 */
class BuildCommand extends Command
{
	/**
	 * An enabled flag.
	 *
	 * @var bool
	 */
	public static $isEnabled = true;

	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name = 'build';

	/**
	 * Property description.
	 *
	 * @var  string
	 */
	protected $description = 'Some useful tools for building system.';

	protected function init()
	{
		$this->addCommand(new GenerateCommand);

  		parent::init();
	}
}
