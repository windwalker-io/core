<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Logger;

use Monolog\Formatter\LineFormatter;
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
     * @param  string $logPath
     */
    public function __construct($logPath)
    {
        $this->logPath = $logPath;
    }

    /**
     * System is unusable.
     *
     * @param string|array $channel
     * @param string|array $message
     * @param array        $context
     *
     * @return static
     */
    public function emergency($channel, $message, array $context = [])
    {
        $this->log($channel, LogLevel::EMERGENCY, $message, $context);

        return $this;
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string|array $channel
     * @param string|array $message
     * @param array        $context
     *
     * @return static
     */
    public function alert($channel, $message, array $context = [])
    {
        $this->log($channel, LogLevel::ALERT, $message, $context);

        return $this;
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string|array $channel
     * @param string|array $message
     * @param array        $context
     *
     * @return static
     */
    public function critical($channel, $message, array $context = [])
    {
        $this->log($channel, LogLevel::CRITICAL, $message, $context);

        return $this;
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string|array $channel
     * @param string|array $message
     * @param array        $context
     *
     * @return static
     */
    public function error($channel, $message, array $context = [])
    {
        $this->log($channel, LogLevel::ERROR, $message, $context);

        return $this;
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string|array $channel
     * @param string|array $message
     * @param array        $context
     *
     * @return static
     */
    public function warning($channel, $message, array $context = [])
    {
        $this->log($channel, LogLevel::WARNING, $message, $context);

        return $this;
    }

    /**
     * Normal but significant events.
     *
     * @param string|array $channel
     * @param string|array $message
     * @param array        $context
     *
     * @return static
     */
    public function notice($channel, $message, array $context = [])
    {
        $this->log($channel, LogLevel::NOTICE, $message, $context);

        return $this;
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string|array $channel
     * @param string|array $message
     * @param array        $context
     *
     * @return static
     */
    public function info($channel, $message, array $context = [])
    {
        $this->log($channel, LogLevel::INFO, $message, $context);

        return $this;
    }

    /**
     * Detailed debug information.
     *
     * @param string|array $channel
     * @param string|array $message
     * @param array        $context
     *
     * @return static
     */
    public function debug($channel, $message, array $context = [])
    {
        $this->log($channel, LogLevel::DEBUG, $message, $context);

        return $this;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param string|array $channel
     * @param string|int   $level
     * @param string|array $message
     * @param array        $context
     *
     * @return static
     */
    public function log($channel, $level, $message, array $context = [])
    {
        if (is_array($channel)) {
            foreach ($channel as $cat) {
                $this->log($cat, $level, $message, $context);
            }

            return $this;
        }

        if (is_array($message)) {
            foreach ($message as $msg) {
                $this->log($channel, $level, $msg, $context);
            }

            return $this;
        }

        $this->getLogger($channel)->log($level, $message, $context);

        return $this;
    }

    /**
     * addLogger
     *
     * @param string          $channel
     * @param LoggerInterface $logger
     *
     * @return  static
     */
    public function addLogger($channel, LoggerInterface $logger)
    {
        $channel = strtolower($channel);

        $this->loggers[$channel] = $logger;

        return $this;
    }

    /**
     * createCategory
     *
     * @param string $channel
     * @param string $level
     *
     * @return  LoggerInterface
     *
     * @deprecated  Use createChannel() instead.
     */
    public function createCategory($channel, $level = Logger::DEBUG)
    {
        return $this->createChannel($channel, $level);
    }

    /**
     * createChannel
     *
     * @param string $channel
     * @param string $level
     *
     * @return  LoggerInterface
     */
    public function createChannel($channel, $level = Logger::DEBUG)
    {
        return $this->getLogger($channel, $level);
    }

    /**
     * getLogger
     *
     * @param string $channel
     * @param string $level
     *
     * @return LoggerInterface
     */
    public function getLogger($channel, $level = Logger::DEBUG)
    {
        $channel = strtolower($channel);

        if (!isset($this->loggers[$channel])) {
            if (class_exists(Monolog::class)) {
                $this->loggers[$channel] = $this->createLogger($channel, $level);

                return $this->loggers[$channel];
            }

            return $this->getNullLogger();
        }

        return $this->loggers[$channel];
    }

    /**
     * createLogger
     *
     * @param string                $categoey
     * @param string                $level
     * @param HandlerInterface|null $handler
     *
     * @return  Monolog
     * @throws \Exception
     */
    public function createLogger($categoey, $level = Logger::DEBUG, HandlerInterface $handler = null)
    {
        $logger = new Monolog($categoey);

        $handler = $handler ?: new StreamHandler($this->getLogFile($categoey), $level);
        $handler->setFormatter(new LineFormatter(null, null, true));

        $logger->pushProcessor(new PsrLogMessageProcessor());

        // Basic string handler
        $logger->pushHandler($handler);

        foreach (GlobalContainer::getHandlers() as $handler) {
            $logger->pushHandler(clone $handler);
        }

        foreach (GlobalContainer::getProcessors() as $processor) {
            $logger->pushProcessor(clone $processor);
        }

        return $logger;
    }

    /**
     * getRotatingLogger
     *
     * @param string $channel
     * @param string $level
     * @param int    $maxFiles
     *
     * @return  LoggerInterface
     */
    public function createRotatingLogger($channel, $level = Logger::DEBUG, $maxFiles = 7)
    {
        return $this->createLogger($channel, $level,
            new RotatingFileHandler($this->getLogFile($channel), $maxFiles, $level));
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
     * @param   string $channel
     *
     * @return  boolean
     */
    public function hasLogger($channel)
    {
        $channel = strtolower($channel);

        return isset($this->loggers[$channel]);
    }

    /**
     * removeLogger
     *
     * @param   string $channel
     *
     * @return  static
     */
    public function removeLogger($channel)
    {
        if (isset($this->loggers[$channel])) {
            unset($this->loggers[$channel]);
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
        foreach ($loggers as $channel => $logger) {
            $this->addLogger($channel, $logger);
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
        if (!$this->nullLogger) {
            $this->nullLogger = new NullLogger();
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
