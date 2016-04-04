<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Migration\Command\Migration;

use Windwalker\Console\Command\AbstractCommand;
use Windwalker\Core\Migration\Model\BackupModel;

/**
 * The CreateCommand class.
 * 
 * @since  2.0
 */
class ResetCommand extends AbstractCommand
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
	protected $name = 'reset';

	/**
	 * The command description.
	 *
	 * @var  string
	 */
	protected $description = 'Reset all migrations.';

	/**
	 * The usage to tell user how to use this command.
	 *
	 * @var string
	 */
	protected $usage = 'create <cmd><command></cmd> <option>[option]</option>';

	/**
	 * Configure command information.
	 *
	 * @return void
	 */
	public function initialise()
	{
		$this->addOption('s')
			->alias('seed')
			->description('Also import seeds.');

		$this->addOption('no-backup')
			->description('Do not backup database.');
	}

	/**
	 * Execute this command.
	 *
	 * @return int|void
	 */
	protected function doExecute()
	{
		// backup
		if (!$this->getOption('no-backup'))
		{
			BackupModel::getInstance()->setCommand($this)->backup();
		}

		$this->out('<cmd>Rollback to 0 version...</cmd>');

		$this->executeCommand(array('migration', 'migrate', '0'));

		$this->out('<cmd>Migrating to latest version...</cmd>');

		$this->executeCommand(array('migration', 'migrate'));

		return true;
	}

	/**
	 * executeCommand
	 *
	 * @param array  $args
	 *
	 * @return  boolean
	 */
	protected function executeCommand($args)
	{
		$io = clone $this->io;

		$io->setArguments($args);
		$io->setOption('no-backup', true);

//		foreach ($this->io->getOptions() as $k => $v)
//		{
//			$io->setOption($k, $v);
//		}

		return $this->app->getRootCommand()->setIO($io)->execute();
	}
}
