<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue\Failer;

/**
 * The NullQueueFailer class.
 *
 * @since  __DEPLOY_VERSION__
 */
class NullQueueFailer implements QueueFailerInterface
{
	/**
	 * add
	 *
	 * @param string $connection
	 * @param string $queue
	 * @param string $body
	 * @param string $exception
	 *
	 * @return  int|string
	 */
	public function add($connection, $queue, $body, $exception)
	{
		return null;
	}

	/**
	 * all
	 *
	 * @return  array
	 */
	public function all()
	{
		return [];
	}

	/**
	 * get
	 *
	 * @param mixed $conditions
	 *
	 * @return  array
	 */
	public function get($conditions)
	{
		return [];
	}

	/**
	 * remove
	 *
	 * @param mixed $conditions
	 *
	 * @return  bool
	 */
	public function remove($conditions)
	{
		return true;
	}

	/**
	 * clear
	 *
	 * @return  bool
	 */
	public function clear()
	{
		return true;
	}
}
