<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Migration\Command\Migration;

use Windwalker\Console\Prompter\BooleanPrompter;
use Windwalker\Core\Console\CoreCommand;
use Windwalker\Core\Migration\Model\BackupModel;
use Windwalker\Core\Migration\Model\MigrationsModel;

/**
 * The MigrateCommand class.
 * 
 * @since  2.0
 */
class MigrateCommand extends CoreCommand
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
	protected $name = 'migrate';

	/**
	 * The command description.
	 *
	 * @var  string
	 */
	protected $description = 'Migrate the database';

	/**
	 * The usage to tell user how to use this command.
	 *
	 * @var string
	 */
	protected $usage = 'migrate <cmd><version></cmd> <option>[option]</option>';

	/**
	 * Configure command information.
	 *
	 * @return void
	 */
	public function init()
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
	 * @throws \Exception
	 */
	protected function doExecute()
	{
		if ($this->console->getMode() != 'dev')
		{
			throw new \RuntimeException('<error>STOP!</error> <comment>you must run migration in dev mode</comment>.');
		}

		$migration = new MigrationsModel;
		$migration->setIo($this->io);

		if (!$this->getOption('no-backup'))
		{
			// backup
			BackupModel::getInstance()->setCommand($this)->backup();
		}

		$migration->setCommand($this);

		$migration['path'] = $this->console->get('migration.dir');

		try
		{
			$migration->migrate($this->getArgument(0, null));

			if ($this->getOption('seed') && ((string) $this->getArgument(0)) != '0')
			{
				$io = clone $this->io;

				$io->setArguments(array('seed', 'import'));
				$io->setOption('no-backup', true);

				$this->console->getRootCommand()->setIO($io)->execute();
			}
		}
		catch (\Exception $e)
		{
			$prompter = new BooleanPrompter;

			$this->out()->out('<error>An error occurred: ' . $e->getMessage() . '</error>');

			if ($prompter->ask('Do you want to restore to last backup? [Y/n] (Y): ', true))
			{
				BackupModel::getInstance()->restoreLatest();
			}

			throw $e;
		}

		return true;
	}
}
 