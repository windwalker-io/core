<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue\Driver;

use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\Queue\QueueMessage;
use Windwalker\Database\Driver\AbstractDatabaseDriver;

/**
 * The DatabaseQueueDriver class.
 *
 * @since  __DEPLOY_VERSION__
 */
class DatabaseQueueDriver implements QueueDriverInterface
{
	/**
	 * Property db.
	 *
	 * @var  AbstractDatabaseDriver
	 */
	protected $db;

	/**
	 * Property table.
	 *
	 * @var
	 */
	protected $table;
	/**
	 * Property queue.
	 *
	 * @var  string
	 */
	private $queue;

	/**
	 * DatabaseQueueDriver constructor.
	 *
	 * @param AbstractDatabaseDriver $db
	 * @param string                 $queue
	 * @param string                 $table
	 */
	public function __construct(AbstractDatabaseDriver $db, $queue = 'default', $table = 'queue_jobs')
	{
		$this->db = $db;
		$this->table = $table;
		$this->queue = $queue;
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
		$time = Chronos::create('now');

		$data = [
			'queue' => $message->getQueueName() ? : $this->queue,
			'body' => json_encode($message),
			'attempts' => 0,
			'created' => $time->toSql(),
			'visibility' => $time->modify(sprintf('+%dseconds', $message->getDelay()))->toSql(),
			'reserved' => null
		];

		$this->db->getWriter()->insertOne($this->table, $data, 'id');

		return $data['id'];
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

		$now = Chronos::create('now');

		$query = $this->db->getQuery(true);

		$query->select('*')
			->from($this->table)
			->where('queue = %q', $queue)
			->where('visibility <= %q', $now->toSql())
			->where('reserved IS NULL')
//			->bind('queue', $queue)
//			->bind('now', $now->toSql())
			->bind($now->toSql());

		$data = $this->db->setQuery($query)->loadOne('assoc');

		if (!$data)
		{
			return null;
		}

		$data['attempts']++;

		$values = ['reserved' => $now->toSql(), 'attempts' => $data['attempts']];

		$this->db->getWriter()->updateBatch($this->table, $values, ['id' => $data['id']]);

		$message = new QueueMessage;

		$message->setId($data['id']);
		$message->setAttempts($data['attempts']);
		$message->setBody(json_decode($data['body'], true));
		$message->setRawBody($data['body']);
		$message->setQueueName($queue);

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
		$queue = $message->getQueueName() ? : $this->queue;

		$query = $this->db->getQuery(true);

		$query->delete($this->table)
			->where('id = :id')
			->where('queue = :queue')
			->bind('id', $message->getId())
			->bind('queue', $queue);

		$this->db->setQuery($query)->execute();

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
		$queue = $message->getQueueName() ? : $this->queue;

		$time = Chronos::create('now');
		$time->modify('+' . $message->getDelay() . 'seconds');

		$values = [
			'reserved' => null,
			'visibility' => $time->toSql()
		];

		$this->db->getWriter()->updateBatch($this->table, $values, [
			'id' => $message->getId(),
			'queue' => $queue
		]);

		return $this;
	}

	/**
	 * Method to get property Table
	 *
	 * @return  mixed
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * Method to set property table
	 *
	 * @param   mixed $table
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setTable($table)
	{
		$this->table = $table;

		return $this;
	}
}
