<?php
/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2015 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

/**
 * The LoggerFactory class.
 *
 * @since  {DEPLOY_VERSION}
 */
class LoggerPool implements \ArrayAccess, \Countable, \IteratorAggregate
{
	/**
	 * Property loggers.
	 *
	 * @var  LoggerInterface[]
	 */
	protected $loggers = array();

	/**
	 * Property nullLogger.
	 *
	 * @var  NullLogger
	 */
	protected $nullLogger;

	/**
	 * System is unusable.
	 *
	 * @param string $category
	 * @param string $message
	 * @param array  $context
	 *
	 * @return null
	 */
	public function emergency($category, $message, array $context = array())
	{
		$this->log($category, LogLevel::EMERGENCY, $message, $context);

		return $this;
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string $category
	 * @param string $message
	 * @param array  $context
	 *
	 * @return null
	 */
	public function alert($category, $message, array $context = array())
	{
		$this->log($category, LogLevel::ALERT, $message, $context);

		return $this;
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $category
	 * @param string $message
	 * @param array  $context
	 *
	 * @return null
	 */
	public function critical($category, $message, array $context = array())
	{
		$this->log($category, LogLevel::CRITICAL, $message, $context);

		return $this;
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $category
	 * @param string $message
	 * @param array  $context
	 *
	 * @return null
	 */
	public function error($category, $message, array $context = array())
	{
		$this->log($category, LogLevel::ERROR, $message, $context);

		return $this;
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string $category
	 * @param string $message
	 * @param array  $context
	 *
	 * @return null
	 */
	public function warning($category, $message, array $context = array())
	{
		$this->log($category, LogLevel::WARNING, $message, $context);

		return $this;
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $category
	 * @param string $message
	 * @param array  $context
	 *
	 * @return null
	 */
	public function notice($category, $message, array $context = array())
	{
		$this->log($category, LogLevel::NOTICE, $message, $context);

		return $this;
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $category
	 * @param string $message
	 * @param array  $context
	 *
	 * @return null
	 */
	public function info($category, $message, array $context = array())
	{
		$this->log($category, LogLevel::INFO, $message, $context);

		return $this;
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $category
	 * @param string $message
	 * @param array  $context
	 *
	 * @return null
	 */
	public function debug($category, $message, array $context = array())
	{
		$this->log($category, LogLevel::DEBUG, $message, $context);

		return $this;
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param string $category
	 * @param mixed  $level
	 * @param string $message
	 * @param array  $context
	 *
	 * @return null
	 */
	public function log($category, $level, $message, array $context = array())
	{
		$this->getLogger($category)->log($level, $message, $context);

		return $this;
	}

	/**
	 * addLogger
	 *
	 * @param string          $category
	 * @param LoggerInterface $logger
	 *
	 * @return  static
	 */
	public function addLogger($category, LoggerInterface $logger)
	{
		$category = strtolower($category);

		$this->loggers[$category] = $logger;

		return $this;
	}

	/**
	 * getLogger
	 *
	 * @param   string $category
	 *
	 * @return  LoggerInterface
	 */
	public function getLogger($category)
	{
		$category = strtolower($category);

		if (!isset($this->loggers[$category]))
		{
			return $this->getNullLogger();
		}

		return $this->loggers[$category];
	}

	/**
	 * hasLogger
	 *
	 * @param   string  $category
	 *
	 * @return  boolean
	 */
	public function hasLogger($category)
	{
		$category = strtolower($category);

		return isset($this->loggers[$category]);
	}

	/**
	 * removeLogger
	 *
	 * @param   string  $category
	 *
	 * @return  static
	 */
	public function removeLogger($category)
	{
		if (isset($this->loggers[$category]))
		{
			unset($this->loggers[$category]);
		}

		return $this;
	}

	/**
	 * Method to get property Loggers
	 *
	 * @return  LoggerInterface[]
	 */
	public function getLoggers()
	{
		return $this->loggers;
	}

	/**
	 * Method to set property loggers
	 *
	 * @param   LoggerInterface[] $loggers
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setLoggers(array $loggers)
	{
		foreach ($loggers as $category => $logger)
		{
			$this->addLogger($category, $logger);
		}

		return $this;
	}

	/**
	 * getNullLogger
	 *
	 * @return  NullLogger
	 */
	public function getNullLogger()
	{
		if (!$this->nullLogger)
		{
			$this->nullLogger = new NullLogger;
		}

		return $this->nullLogger;
	}

	/**
	 * Retrieve an external iterator
	 *
	 * @return \Traversable An instance of an object implementing Iterator or Traversable
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->loggers);
	}

	/**
	 * Is a property exists or not.
	 *
	 * @param mixed $offset Offset key.
	 *
	 * @return  boolean
	 */
	public function offsetExists($offset)
	{
		return $this->hasLogger($offset);
	}

	/**
	 * Get a property.
	 *
	 * @param mixed $offset Offset key.
	 *
	 * @throws  \InvalidArgumentException
	 * @return  mixed The value to return.
	 */
	public function offsetGet($offset)
	{
		return $this->getLogger($offset);
	}

	/**
	 * Set a value to property.
	 *
	 * @param mixed $offset Offset key.
	 * @param mixed $value  The value to set.
	 *
	 * @throws  \InvalidArgumentException
	 * @return  void
	 */
	public function offsetSet($offset, $value)
	{
		$this->addLogger($offset, $value);
	}

	/**
	 * Unset a property.
	 *
	 * @param mixed $offset Offset key to unset.
	 *
	 * @throws  \InvalidArgumentException
	 * @return  void
	 */
	public function offsetUnset($offset)
	{
		$this->removeLogger($offset);
	}

	/**
	 * Count this object.
	 *
	 * @return  int
	 */
	public function count()
	{
		return count($this->loggers);
	}
}
