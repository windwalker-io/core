<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Migration\Command\Migration;

use Windwalker\Console\Prompter\BooleanPrompter;
use Windwalker\Core\Console\CoreCommand;
use Windwalker\Core\Console\CoreCommandTrait;
use Windwalker\Core\Ioc;
use Windwalker\Core\Migration\Model\BackupModel;

/**
 * The DropAllCommand class.
 *
 * @since  {DEPLOY_VERSION}
 */
class DropAllCommand extends CoreCommand
{
	use CoreCommandTrait;
	
	/**
	 * Console(Argument) name.
	 *
	 * @var  string
	 */
	protected $name = 'drop-all';

	/**
	 * The command description.
	 *
	 * @var  string
	 */
	protected $description = 'Drop all tables if migration can not work.';

	/**
	 * The usage to tell user how to use this command.
	 *
	 * @var string
	 */
	protected $usage = 'drop-all <option>[option]</option>';

	/**
	 * Configure command information.
	 *
	 * @return void
	 */
	public function init()
	{
	}

	/**
	 * Execute this command.
	 *
	 * @return int|void
	 */
	protected function doExecute()
	{
		if ($this->console->getMode() != 'dev')
		{
			throw new \RuntimeException('<error>STOP!</error> <comment>you must run migration in dev mode</comment>.');
		}

		if (!(new BooleanPrompter)->ask('This action will drop all tables, do you really want to do this? [N/y]', false))
		{
			$this->out('  Canceled.');

			return false;
		}

		if (!$this->io->getOption('no-backup'))
		{
			// backup
			BackupModel::getInstance()->setCommand($this)->backup();
		}

		$db = Ioc::getDatabase();

		$tables = $db->getDatabase()->getTables(true);

		foreach ($tables as $table)
		{
			$db->getTable($table, true)->drop(true);

			$this->out('  Drop table: <comment>' . $table . '</comment>');
		}

		return true;
	}
}
