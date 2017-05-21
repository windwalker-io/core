<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue;

use Windwalker\Core\Queue\Driver\AbstractQueueDriver;
use Windwalker\Core\Queue\Driver\SqsQueueDriver;

/**
 * The QueueManager class.
 *
 * @since  __DEPLOY_VERSION__
 */
class QueueManager
{
	/**
	 * Property driver.
	 *
	 * @var AbstractQueueDriver
	 */
	protected $driver;

	/**
	 * QueueManager constructor.
	 *
	 * @param AbstractQueueDriver $driver
	 */
	public function __construct(AbstractQueueDriver $driver)
	{
		$this->driver = $driver;
	}

	public function push()
	{

	}

	public function pop()
	{

	}

	public function delete()
	{

	}
}
