<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Queue;

use Windwalker\Data\Data;

/**
 * The MessageResponse class.
 *
 * @since  __DEPLOY_VERSION__
 */
class MessageResponse
{
	/**
	 * Property id.
	 *
	 * @var  string|int
	 */
	public $id = '';

	/**
	 * Property attempts.
	 *
	 * @var  int
	 */
	public $attempts = 0;

	/**
	 * Property queue.
	 *
	 * @var  string
	 */
	public $queue = '';

	/**
	 * Property body.
	 *
	 * @var  array
	 */
	public $body = [];

	/**
	 * Property rawData.
	 *
	 * @var  array
	 */
	public $rawData = [];

	/**
	 * get
	 *
	 * @param string $name
	 * @param mixed  $default
	 *
	 * @return  mixed
	 */
	public function get($name, $default = null)
	{
		if (isset($this->body[$name]))
		{
			return $this->body[$name];
		}

		return $default;
	}

	/**
	 * set
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @return  static
	 */
	public function set($name, $value)
	{
		$this->body[$name] = $value;

		return $this;
	}

	/**
	 * Method to get property Id
	 *
	 * @return  int|string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Method to set property id
	 *
	 * @param   int|string $id
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setId($id)
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * Method to get property Attempts
	 *
	 * @return  int
	 */
	public function getAttempts()
	{
		return $this->attempts;
	}

	/**
	 * Method to set property attempts
	 *
	 * @param   int $attempts
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setAttempts($attempts)
	{
		$this->attempts = $attempts;

		return $this;
	}

	/**
	 * Method to get property Body
	 *
	 * @return  array
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * Method to set property body
	 *
	 * @param   array $body
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setBody(array $body)
	{
		$this->body = $body;

		return $this;
	}

	/**
	 * Method to get property RawData
	 *
	 * @return  array
	 */
	public function getRawData()
	{
		return $this->rawData;
	}

	/**
	 * Method to set property rawData
	 *
	 * @param   array $rawData
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setRawData(array $rawData)
	{
		$this->rawData = $rawData;

		return $this;
	}

	/**
	 * Method to get property Queue
	 *
	 * @return  string
	 */
	public function getQueue()
	{
		return $this->queue;
	}

	/**
	 * Method to set property queue
	 *
	 * @param   string $queue
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setQueue($queue)
	{
		$this->queue = $queue;

		return $this;
	}

}
