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
 * The AbstractQueueDriver class.
 *
 * @since  __DEPLOY_VERSION__
 */
abstract class AbstractQueueDriver
{
	/**
	 * push
	 *
	 * @param QueueMessage $message
	 *
	 * @return int|string
	 * @internal param array $options
	 *
	 * @internal param string $queue
	 * @internal param string $body
	 * @internal param int $delay
	 */
	abstract public function push(QueueMessage $message);

	/**
	 * pop
	 *
	 * @param string $queue
	 *
	 * @return QueueMessage
	 */
	abstract public function pop($queue = null);

	/**
	 * delete
	 *
	 * @param QueueMessage|string $message
	 *
	 * @return static
	 * @internal param null $queue
	 *
	 */
	abstract public function delete(QueueMessage $message);

	/**
	 * release
	 *
	 * @param QueueMessage|string $message
	 *
	 * @return static
	 * @internal param string $queue
	 *
	 * @internal param int $delay
	 */
	abstract public function release(QueueMessage $message);
}
