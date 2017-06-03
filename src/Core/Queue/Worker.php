<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue;

use Windwalker\Core\Event\EventDispatcher;
use Windwalker\Core\Logger\LoggerManager;
use Windwalker\Core\Queue\Exception\MaxAttemptsExceededException;
use Windwalker\Core\Queue\Job\JobInterface;
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\DispatcherAwareTrait;
use Windwalker\Structure\Structure;

/**
 * The Worker class.
 *
 * @since  __DEPLOY_VERSION__
 */
class Worker implements DispatcherAwareInterface
{
	use DispatcherAwareTrait;

	/**
	 * Property dispatcher.
	 *
	 * @var  EventDispatcher
	 */
	protected $dispatcher;

	/**
	 * Property queue.
	 *
	 * @var  QueueManager
	 */
	protected $manager;

	/**
	 * Property logger.
	 *
	 * @var  LoggerManager
	 */
	protected $logger;

	/**
	 * Property exiting.
	 *
	 * @var bool
	 */
	protected $exiting = false;

	/**
	 * Worker constructor.
	 *
	 * @param QueueManager    $manager
	 * @param EventDispatcher $dispatcher
	 * @param LoggerManager   $logger
	 */
	public function __construct(QueueManager $manager, EventDispatcher $dispatcher, LoggerManager $logger)
	{
		$this->dispatcher = $dispatcher;
		$this->manager = $manager;
		$this->logger = $logger;
	}

	/**
	 * loop
	 *
	 * @param string|array $queue
	 * @param Structure    $options
	 *
	 * @return  void
	 */
	public function loop($queue, Structure $options)
	{
		gc_enable();

		// Last Restart

		while (true)
		{
			$this->gc();

			// Timeout handler
			$this->registerSignals($options);

			// if ($this->canLoop() || $options->get('force'))
			{
				$this->runNextJob($queue, $options);
			}

			if ((memory_get_usage() / 1024 / 1024) >= (int) $options->get('memory_limit', 128))
			{
				$this->stop('Memory usage exceeded.');
			}

			if ($this->exiting)
			{
				$this->stop('Shotdown by signal.');
			}

			$this->sleep((int) $options->get('sleep'));
		}
	}

	/**
	 * runNextJob
	 *
	 * @param string|array $queue
	 * @param Structure    $options
	 *
	 * @return  void
	 */
	public function runNextJob($queue, Structure $options)
	{
		$message = $this->getNextMessage($queue);

		if (!$message)
		{
			return;
		}

		$maxTries = (int) $options->get('tries', 5);

		$job = $message->getJob();
		/** @var JobInterface $job */
		$job = unserialize($job);

		try
		{
			// @before event
			$this->dispatcher->triggerEvent('onWorkBeforeJobRun', [
				'worker' => $this,
				'job' => $job,
				'manager' => $this->manager
			]);

			// Fail if max attempts
			if ($maxTries !== 0 && $maxTries < $message->getAttempts())
			{
				$this->manager->delete($message);

				throw new MaxAttemptsExceededException('Max attempts exceed for Message: ' . $message->getId());
			}

			// run
			$this->manager->getContainer()->execute([$job, 'execute']);

			// @after event
			$this->dispatcher->triggerEvent('onWorkAfterJobRun', [
				'worker' => $this,
				'job' => $job,
				'manager' => $this->manager
			]);

			$this->manager->delete($message);
		}
		catch (\Exception $e)
		{
			$this->handleJobException($job, $e);
		}
		catch (\Throwable $t)
		{
			$this->handleJobException($job, $t);
		}
		finally
		{
			if ($maxTries !== 0 && $maxTries <= $message->getAttempts())
			{
				$this->manager->delete($message);
			}

			if (!$message->isDeleted())
			{
				$this->manager->release($message, (int) $options->get('delay', 0));
			}
		}
	}

	/**
	 * registerTimeoutHandler
	 *
	 * @param Structure $options
	 *
	 * @return  void
	 */
	protected function registerSignals(Structure $options)
	{
		$timeout = (int) $options->get('timeout', 60);

		if (!extension_loaded('pcntl'))
		{
			return;
		}

		if (version_compare(PHP_VERSION, '7.1', '>='))
		{
			pcntl_async_signals(true);
		}
		else
		{
			declare (ticks = 1);
		}

		if ($timeout !== 0)
		{
			pcntl_signal(SIGALRM, function () use ($timeout)
			{
				$this->stop('A job process over the max timeout: ' . $timeout);
			});

			pcntl_alarm($timeout + $options->get('sleep'));
		}

		// Wait job complete then stop
		pcntl_signal(SIGINT, [$this, 'showdown']);
		pcntl_signal(SIGTERM, [$this, 'showdown']);
	}

	/**
	 * shoutdown
	 *
	 * @return  void
	 */
	public function shoutdown()
	{
		$this->exiting = true;
	}

	/**
	 * stop
	 *
	 * @param string $reason
	 *
	 * @return void
	 */
	public function stop($reason = 'Unkonwn reason')
	{
		$this->logger->info('queue', 'Worker stop: ' . $reason);

		$this->triggerEvent('onWorkerStop', [
			'worker' => $this
		]);

		exit();
	}

	/**
	 * handleException
	 *
	 * @param JobInterface          $job
	 * @param \Exception|\Throwable $e
	 *
	 * @return void
	 */
	protected function handleJobException(JobInterface $job, $e)
	{
		$this->logger->error('queue', sprintf(
			'%s : %s',
			get_class($e),
			$e->getMessage()
		));

		if (method_exists($job, 'failed'))
		{
			$job->failed($e);
		}

		$this->dispatcher->triggerEvent('onWorkJobFailure', [
			'worker' => $this,
			'exception' => $e,
			'job' => $job
		]);
	}

	/**
	 * getNextMessage
	 *
	 * @param $queue
	 *
	 * @return  null|QueueMessage
	 */
	protected function getNextMessage($queue)
	{
		$queue = (array) $queue;

		foreach ($queue as $queueName)
		{
			if ($message = $this->manager->pop($queueName))
			{
				return $message;
			}
		}

		return null;
	}

	/**
	 * sleep
	 *
	 * @param int $seconds
	 *
	 * @return  void
	 */
	protected function sleep($seconds)
	{
		sleep($seconds);
	}

	/**
	 * Method to perform basic garbage collection and memory management in the sense of clearing the
	 * stat cache.  We will probably call this method pretty regularly in our main loop.
	 *
	 * @return  void
	 */
	protected function gc()
	{
		// Perform generic garbage collection.
		gc_collect_cycles();

		// Clear the stat cache so it doesn't blow up memory.
		clearstatcache();
	}

	/**
	 * Method to get property Manager
	 *
	 * @return  QueueManager
	 */
	public function getManager()
	{
		return $this->manager;
	}
}
