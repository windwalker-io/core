<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue\Driver;

use Windwalker\Core\Queue\QueueMessage;
use Windwalker\Core\Queue\Worker;
use Windwalker\DI\Container;
use Windwalker\Event\Event;
use Windwalker\Structure\Structure;

/**
 * The SyncQueueDriver class.
 *
 * @since  __DEPLOY_VERSION__
 */
class SyncQueueDriver implements QueueDriverInterface
{
	/**
	 * Property worker.
	 *
	 * @var  Container
	 */
	protected $container;

	/**
	 * SyncQueueDriver constructor.
	 *
	 * @param Container $container
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * push
	 *
	 * @param QueueMessage $message
	 *
	 * @return int|string
	 */
	public function push(QueueMessage $message)
	{
		/** @var Worker $worker */
		$worker = $this->container->get('queue.worker');

		$worker->getDispatcher()->listen('onWorkerJobFailure', function (Event $event)
		{
			throw $event['exception'];
		});

		$worker->process($message, new Structure);

		return 0;
	}

	/**
	 * pop
	 *
	 * @param string $queue
	 *
	 * @return QueueMessage
	 */
	public function pop($queue = null)
	{
		return new QueueMessage;
	}

	/**
	 * delete
	 *
	 * @param QueueMessage|string $message
	 *
	 * @return static
	 */
	public function delete(QueueMessage $message)
	{
		return $this;
	}

	/**
	 * release
	 *
	 * @param QueueMessage|string $message
	 *
	 * @return static
	 */
	public function release(QueueMessage $message)
	{
		return $this;
	}
}
