<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Migration\Command\Migration;

use Windwalker\Console\Command\AbstractCommand;
use Windwalker\Core\Ioc;
use Windwalker\Core\Migration\Model\MigrationsModel;

/**
 * The MigrateCommand class.
 * 
 * @since  {DEPLOY_VERSION}
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
	}

	/**
	 * Execute this command.
	 *
	 * @return int|void
	 */
	protected function doExecute()
	{
		$migration = new MigrationsModel;

		$migration->setCommand($this);

		$migration['path'] = $this->getOption('p');

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

		return true;
	}
}
 