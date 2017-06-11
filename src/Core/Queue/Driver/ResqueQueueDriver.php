<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue\Driver;

use Resque;
use Windwalker\Core\Queue\QueueMessage;

/**
 * The ResqueQueueDriver class.
 *
 * @since  __DEPLOY_VERSION__
 */
class ResqueQueueDriver implements QueueDriverInterface
{
	/**
	 * Property queue.
	 *
	 * @var  string
	 */
	protected $queue;

	/**
	 * ResqueQueueDriver constructor.
	 *
	 * @param string $host
	 * @param int    $port
	 * @param string $queue
	 */
	public function __construct($host = 'localhost', $port = 6379, $queue = 'default')
	{
		$this->queue = $queue;

		$this->connect($host, $port);
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
		$queue = $message->getQueueName() ? : $this->queue;

		$delay = $message->getDelay();

		$message->set('attempts', 0);
		$message->set('queue', $queue);
		$data = json_decode(json_encode($message), true);

		if ($delay > 0)
		{
			\ResqueScheduler::delayedPush(time() + $delay, $data);
		}
		else
		{
			Resque::push($queue, $data);
		}

		return 1;
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
		$queue = $queue ? : $this->queue;

		$job = Resque::pop($queue);
		
		if (!$job)
		{
			return null;
		}

		$message = new QueueMessage;

		$message->setId($result->id);
		$message->setAttempts($result->reserved_count);
		$message->setBody(json_decode($result->body, true));
		$message->setRawBody($result->body);
		$message->setQueueName($queue ? : $this->queue);
		$message->set('reservation_id', $result->reservation_id);

		return $message;
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
		Resque::dequeue();
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

	}

	public function connect($host, $port)
	{
		Resque::setBackend("$host:$port");
	}
}
