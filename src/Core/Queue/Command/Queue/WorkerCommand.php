<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue\Command\Queue;

use Windwalker\Console\Command\Command;
use Windwalker\Core\Console\CoreConsole;
use Windwalker\Core\Queue\Driver\SqsQueueDriver;
use Windwalker\Core\Queue\QueueManager;

/**
 * The WorkerCommand class.
 *
 * @since  __DEPLOY_VERSION__
 */
class WorkerCommand extends Command
{
	/**
	 * Property name.
	 *
	 * @var  string
	 */
	protected $name = 'worker';

	/**
	 * Property description.
	 *
	 * @var  string
	 */
	protected $description = 'Start a queue worker.';

	/**
	 * init
	 *
	 * @return  void
	 */
	protected function init()
	{
		parent::init();
	}

	protected function doExecute()
	{
		/** @var CoreConsole $app */
		$app = $this->getApplication();

		$queue = $app->queue;

		do
		{
			$message = $queue->pop();

			if ($message !== false)
			{
				$job = $message->getJob();

				$queue->runJob($job);

				$queue->release($message);
			}
		}
		while ($message !== false);

		return true;
	}
}
