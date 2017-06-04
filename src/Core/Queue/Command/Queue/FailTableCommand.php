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
use Windwalker\Core\Queue\Job\JobInterface;
use Windwalker\Core\Queue\QueueMessage;
use Windwalker\Core\Queue\Worker;
use Windwalker\Event\Event;
use Windwalker\Structure\Structure;

/**
 * The WorkerCommand class.
 *
 * @since  __DEPLOY_VERSION__
 */
class FailTableCommand extends Command
{
	use CoreCommandTrait;
	use MigrationCommandTrait;

	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name = 'fail-table';

	/**
	 * Property description.
	 *
	 * @var  string
	 */
	protected $description = 'Create fail-jobs migraiton file.';

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
			$this->getArgument(0, 'QueueFailJobInit'),
			__DIR__ . '/../../../Resources/Templates/migration/queue_fail_jobs.tpl'
		);

		return true;
	}
}
