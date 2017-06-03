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
use Windwalker\Core\Console\CoreConsole;
use Windwalker\Core\Queue\Job\JobInterface;
use Windwalker\Core\Queue\Worker;
use Windwalker\Dom\DomElement;
use Windwalker\Event\Event;
use Windwalker\Structure\Structure;

/**
 * The WorkerCommand class.
 *
 * @since  __DEPLOY_VERSION__
 */
class WorkerCommand extends Command
{
	use CoreCommandTrait;

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
		$this->addOption('once')
			->alias('o')
			->defaultValue(false)
			->description('Only run next job.');

		$this->addOption('delay')
			->alias('d')
			->defaultValue(0)
			->description('Delay time for failed job to wait next run.');

		$this->addOption('force')
			->alias('f')
			->defaultValue(false)
			->description('Force run worker if in pause mode.');

		$this->addOption('memory')
			->alias('m')
			->defaultValue(128)
			->description('The memory limit in megabytes.');

		$this->addOption('sleep')
			->alias('s')
			->defaultValue(3)
			->description('Number of seconds to sleep after job run complete.');

		$this->addOption('timeout')
			->defaultValue(60)
			->description('Number of seconds that a job can run.');

		$this->addOption('tries')
			->alias('t')
			->defaultValue(5)
			->description('Number of times to attempt a job if it failed.');
	}

	/**
	 * doExecute
	 *
	 * @return  bool
	 */
	protected function doExecute()
	{
		$queues  = $this->io->getArguments();
		$options = new Structure($this->io->getOptions());

		/** @var Worker $worker */
		$worker = $this->console->container->get('queue.worker');

		// Default Queues
		if (!count($queues))
		{
			$driver = $this->console->get('queue.driver', 'sync');
			$queues = $this->console->get('queue.' . $driver . '.default');
		}

		$this->listenToWorker($worker);

		if ($this->getOption('once'))
		{
			$worker->runNextJob($queues, $options);
		}
		else
		{
			$worker->loop($queues, $options);
		}

		return true;
	}

	/**
	 * listenToWorker
	 *
	 * @param Worker $worker
	 *
	 * @return  void
	 */
	protected function listenToWorker(Worker $worker)
	{
		$worker->getDispatcher()
			->listen('onWorkBeforeJobRun', function (Event $event)
			{
				/** @var JobInterface $job */
				$job = $event['job'];

			    $this->console->addMessage('Run Job: ' . new DomElement('info', $job->getName()));
			})
			->listen('onWorkJobFailure', function (Event $event)
			{
				/**
				 * @var JobInterface $job
				 * @var \Exception   $e
				 */
				$job = $event['job'];
				$e = $event['exception'];

				$this->console->addMessage(sprintf(
					'Job %s failed: %s',
					$job->getName(),
					$e->getMessage()
				), 'error');
			});
	}
}
