<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue\Driver;

use Windwalker\Core\Queue\MessageResponse;

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
	 * @param string $body
	 * @param string $queue
	 * @param int    $delay
	 * @param array  $options
	 *
	 * @return string|int
	 */
	abstract public function push($body, $queue = null, $delay = 0, array $options = []);

	/**
	 * pop
	 *
	 * @param string $queue
	 *
	 * @return MessageResponse
	 */
	abstract public function pop($queue = null);

	/**
	 * delete
	 *
	 * @param MessageResponse|string $message
	 * @param null                   $queue
	 *
	 * @return static
	 */
	abstract public function delete($message, $queue = null);

	/**
	 * release
	 *
	 * @param MessageResponse|string $message
	 * @param int                    $delay
	 * @param string                 $queue
	 *
	 * @return  static
	 */
	abstract public function release($message, $delay = 0, $queue = null);
}
