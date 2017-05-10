<?php

namespace Windwalker\SystemPackage\Command;

use Windwalker\Console\Command\Command;
use Windwalker\SystemPackage\Command\System\ClearCacheCommand;
use Windwalker\SystemPackage\Command\System\DownCommand;
use Windwalker\SystemPackage\Command\System\GenerateCommand;
use Windwalker\SystemPackage\Command\System\ModeCommand;
use Windwalker\SystemPackage\Command\System\UpCommand;

/**
 * Class BuildCommand
 *
 * @since 1.0
 */
class SystemCommand extends Command
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
	protected $name = 'system';

	/**
	 * Property description.
	 *
	 * @var  string
	 */
	protected $description = 'System operation.';

	/**
	 * init
	 *
	 * @return  void
	 */
	protected function init()
	{
		$this->addCommand(GenerateCommand::class);
		$this->addCommand(UpCommand::class);
		$this->addCommand(DownCommand::class);
		$this->addCommand(ModeCommand::class);
		$this->addCommand(ClearCacheCommand::class);

  		parent::init();
	}
}
