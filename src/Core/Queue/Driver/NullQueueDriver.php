<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue\Driver;

use Windwalker\Core\Queue\QueueMessage;

/**
 * The NullQueueDriver class.
 *
 * @since  __DEPLOY_VERSION__
 */
class NullQueueDriver implements QueueDriverInterface
{
	/**
	 * push
	 *
	 * @param QueueMessage $message
	 *
	 * @return int|string
	 */
	public function push(QueueMessage $message)
	{
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
