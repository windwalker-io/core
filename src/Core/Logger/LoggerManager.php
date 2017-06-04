<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Logger;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as Monolog;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Windwalker\Core\Logger\Monolog\GlobalContainer;

/**
 * The LoggerFactory class.
 *
 * @since  2.1.1
 */
class LoggerManager implements \ArrayAccess, \Countable, \IteratorAggregate
{
	/**
	 * Property loggers.
	 *
	 * @var  LoggerInterface[]
	 */
	protected $loggers = [];

	/**
	 * Property nullLogger.
	 *
	 * @var  NullLogger
	 */
	protected $nullLogger;

	/**
	 * Property logPath.
	 *
	 * @var
	 */
	protected $logPath;

	/**
	 * LoggerPool constructor.
	 *
	 * @param  string  $logPath
	 */
	public function __construct($logPath)
	{
		$this->logPath = $logPath;
	}

	/**
	 * System is unusable.
	 *
	 * @param string|array  $category
	 * @param string|array  $message
	 * @param array         $context
	 *
	 * @return static
	 */
	public function emergency($category, $message, array $context = [])
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
	 * @param string|array  $category
	 * @param string|array  $message
	 * @param array         $context
	 *
	 * @return static
	 */
	public function alert($category, $message, array $context = [])
	{
		$this->log($category, LogLevel::ALERT, $message, $context);

		return $this;
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string|array  $category
	 * @param string|array  $message
	 * @param array         $context
	 *
	 * @return static
	 */
	public function critical($category, $message, array $context = [])
	{
		$this->log($category, LogLevel::CRITICAL, $message, $context);

		return $this;
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string|array  $category
	 * @param string|array  $message
	 * @param array         $context
	 *
	 * @return static
	 */
	public function error($category, $message, array $context = [])
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
	 * @param string|array  $category
	 * @param string|array  $message
	 * @param array         $context
	 *
	 * @return static
	 */
	public function warning($category, $message, array $context = [])
	{
		$this->log($category, LogLevel::WARNING, $message, $context);

		return $this;
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string|array  $category
	 * @param string|array  $message
	 * @param array         $context
	 *
	 * @return static
	 */
	public function notice($category, $message, array $context = [])
	{
		$this->log($category, LogLevel::NOTICE, $message, $context);

		return $this;
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string|array  $category
	 * @param string|array  $message
	 * @param array         $context
	 *
	 * @return static
	 */
	public function info($category, $message, array $context = [])
	{
		$this->log($category, LogLevel::INFO, $message, $context);

		return $this;
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string|array  $category
	 * @param string|array  $message
	 * @param array         $context
	 *
	 * @return static
	 */
	public function debug($category, $message, array $context = [])
	{
		$this->log($category, LogLevel::DEBUG, $message, $context);

		return $this;
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param string|array $category
	 * @param string|int   $level
	 * @param string|array $message
	 * @param array        $context
	 *
	 * @return static
	 */
	public function log($category, $level, $message, array $context = [])
	{
		if (is_array($category))
		{
			foreach ($category as $cat)
			{
				$this->log($cat, $level, $message, $context);
			}

			return $this;
		}

		if (is_array($message))
		{
			foreach ($message as $msg)
			{
				$this->log($category, $level, $msg, $context);
			}

			return $this;
		}

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
	 * createCategory
	 *
	 * @param string  $category
	 * @param string  $level
	 *
	 * @return  LoggerInterface
	 */
	public function createCategory($category, $level = Logger::DEBUG)
	{
		return $this->getLogger($category, $level);
	}

	/**
	 * getLogger
	 *
	 * @param string  $category
	 * @param string  $level
	 *
	 * @return LoggerInterface
	 */
	public function getLogger($category, $level = Logger::DEBUG)
	{
		$category = strtolower($category);

		if (!isset($this->loggers[$category]))
		{
			if (class_exists(Monolog::class))
			{
				$this->loggers[$category] = $this->createLogger($category, $level);

				return $this->loggers[$category];
			}

			return $this->getNullLogger();
		}

		return $this->loggers[$category];
	}

	/**
	 * createLogger
	 *
	 * @param string                $categoey
	 * @param int                   $level
	 * @param HandlerInterface|null $handler
	 *
	 * @return  Monolog
	 */
	public function createLogger($categoey, $level = Logger::DEBUG, HandlerInterface $handler = null)
	{
		$logger = new Monolog($categoey);

		$handler = $handler ? : new StreamHandler($this->getLogFile($categoey), $level);
		$logger->pushProcessor(new PsrLogMessageProcessor);

		// Basic string handler
		$logger->pushHandler($handler);

		foreach (GlobalContainer::getHandlers() as $handler)
		{
			$logger->pushHandler(clone $handler);
		}

		foreach (GlobalContainer::getProcessors() as $processor)
		{
			$logger->pushProcessor(clone $processor);
		}

		return $logger;
	}

	/**
	 * getRotatingLogger
	 *
	 * @param string $category
	 * @param string $level
	 * @param int    $maxFiles
	 *
	 * @return  LoggerInterface
	 */
	public function createRotatingLogger($category, $level = Logger::DEBUG, $maxFiles = 7)
	{
		return $this->createLogger($category, $level, new RotatingFileHandler($this->getLogFile($category), $maxFiles, $level));
	}

	/**
	 * getLogFile
	 *
	 * @param string $categoey
	 *
	 * @return  string
	 */
	public function getLogFile($categoey)
	{
		return $this->logPath . '/' . $categoey . '.log';
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

	/**
	 * Method to get property LogPath
	 *
	 * @return  mixed
	 */
	public function getLogPath()
	{
		return $this->logPath;
	}

	/**
	 * Method to set property logPath
	 *
	 * @param   mixed $logPath
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setLogPath($logPath)
	{
		$this->logPath = $logPath;

		return $this;
	}
}
