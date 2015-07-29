<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Migration\Command\Migration;

use Windwalker\Console\Command\AbstractCommand;
use Windwalker\Console\Prompter\BooleanPrompter;
use Windwalker\Core\Migration\Model\BackupModel;
use Windwalker\Core\Migration\Model\MigrationsModel;

/**
 * The MigrateCommand class.
 * 
 * @since  2.0
 */
class MigrateCommand extends AbstractCommand
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
	public function initialise()
	{
		$this->addOption('s')
			->alias('seed')
			->description('Also import seeds.');
	}

	/**
	 * Execute this command.
	 *
	 * @return int|void
	 * @throws \Exception
	 */
	protected function doExecute()
	{
		$migration = new MigrationsModel;

		if (!$this->io->getOption('no-backup'))
		{
			// backup
			BackupModel::getInstance()->setCommand($this)->backup();
		}

		$migration->setCommand($this);

		$migration['path'] = $this->app->get('migration.dir');

		try
		{
			$migration->migrate($this->getArgument(0, null));

			$logs = (array) $migration['log'];

			if ($logs)
			{
				$tmpl = <<<LOG

	Migration <cmd>%s</cmd> the version: <info>%s_%s</info>
	------------------------------------------------------------
	<option>Success</option>

LOG;

				foreach ($logs as $log)
				{
					$this->out(sprintf(
						$tmpl,
						strtoupper($log['direction']),
						$log['id'],
						$log['name']
					));
				}
			}
			else
			{
				$this->out('No change.');
			}

			if ($this->getOption('seed') && ((string) $this->getArgument(0)) != '0')
			{
				$io = clone $this->io;

				$io->setArguments(array('seed', 'import'));
				$io->setOption('no-backup', true);

	//			foreach ($this->io->getOptions() as $k => $v)
	//			{
	//				$io->setOption($k, $v);
	//			}

				$this->app->getRootCommand()->setIO($io)->execute();
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
 