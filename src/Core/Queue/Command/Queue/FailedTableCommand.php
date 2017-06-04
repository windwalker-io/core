<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue\Command\Queue;

use Windwalker\Console\Command\Command;
use Windwalker\Core\Console\CoreCommandTrait;
use Windwalker\Core\Migration\Command\MigrationCommandTrait;

/**
 * The WorkerCommand class.
 *
 * @since  __DEPLOY_VERSION__
 */
class FailedTableCommand extends Command
{
	use CoreCommandTrait;
	use MigrationCommandTrait;

	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name = 'failed-table';

	/**
	 * Property description.
	 *
	 * @var  string
	 */
	protected $description = 'Create failed_jobs migraiton file.';

	/**
	 * Property usage.
	 *
	 * @var  string
	 */
	protected $usage = '%s <cmd><class_name></cmd> <option>[option]</option>';

	/**
	 * init
	 *
	 * @return  void
	 */
	protected function init()
	{
	}

	/**
	 * doExecute
	 *
	 * @return  bool
	 */
	protected function doExecute()
	{
		$repository = $this->getRepository();

		$repository->copyMigration(
			$this->getArgument(0, 'QueueFailedJobInit'),
			__DIR__ . '/../../../Resources/Templates/migration/queue_failed_jobs.tpl'
		);

		return true;
	}
}
