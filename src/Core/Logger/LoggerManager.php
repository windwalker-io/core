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
use Windwalker\Core\Config\Config;
use Windwalker\Core\Logger\Monolog\GlobalContainer;
use Windwalker\DI\ClassMeta;
use Windwalker\DI\Container;

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
     * Property container.
     *
     * @var  Container
     */
    protected $container;

    /**
     * Property config.
     *
     * @var  Config
     */
    protected $config;

    /**
     * LoggerPool constructor.
     *
     * @param  string   $logPath
     * @param Config    $config
     * @param Container $container
     */
    public function __construct(string $logPath, Config $config, Container $container)
    {
        $this->logPath = $logPath;
        $this->container = $container;
        $this->config = $config;
    }

    /**
     * System is unusable.
     *
     * @param string|array $channel
     * @param string|array $message
     * @param array        $context
     *
     * @return static
     * @throws \Exception
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
     * @throws \Exception
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
     * @throws \Exception
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
     * @throws \Exception
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
     * @throws \Exception
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
     * @throws \Exception
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
     * @throws \Exception
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
     * @throws \Exception
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
     * @throws \Exception
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
     * @throws \Exception
     * @deprecated  Use createChannel() instead.
     */
    public function createCategory(string $channel, ?string $level = null): LoggerInterface
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
     * @throws \Exception
     */
    public function createChannel(string $channel, ?string $level = null): LoggerInterface
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
     * @throws \Exception
     */
    public function getLogger(string $channel, ?string $level = null): LoggerInterface
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
     * @param string                $channel
     * @param string                $level
     * @param HandlerInterface|null $handler
     *
     * @return  Monolog
     * @throws \Exception
     */
    public function createLogger(
        ?string $channel = null,
        ?string $level = null,
        HandlerInterface $handler = null
    ): LoggerInterface {
        $config = $this->config->extract('logs.channels.' . $channel);

        if ($config->get('enabled') === false) {
            return $this->getNullLogger();
        }

        $level = $level ?: $config->get('level', LogLevel::DEBUG);

        $logger = new Monolog($channel);

        if (!$handler) {
            $handlers = [];
            $handlerProfiles = (array) $config->get(
                'handlers',
                [
                    $this->config->get('logs.handlers.default', StreamHandler::class)
                ]
            );

            foreach ($handlerProfiles as $class) {
                $args = [];

                $args['level'] = $level;

                if ($class instanceof ClassMeta) {
                    $args['channel'] = $channel;

                    if (!$class->hasArgument('filename')) {
                        $args['filename'] = $this->getLogFile($channel);
                    }

                    $handler = $this->container->newInstance($class, $args);
                } else {
                    if (is_array($class)) {
                        $args = $class['args'] ?? [];
                        $args['level'] = $class['level'] ?? $level;

                        if (isset($class['file_argument'])) {
                            $args[$class['file_argument']] = $this->getLogFile($channel);
                        }

                        $class = $class['class'];
                    }

                    switch ($class) {
                        case 'stream':
                        case StreamHandler::class:
                            $args['stream'] = $args['stream'] ?? $this->getLogFile($channel);

                            $handler = $this->container->newInstance(
                                StreamHandler::class,
                                $args
                            );
                            break;

                        case 'rotating':
                        case RotatingFileHandler::class:
                            $args['filename'] = $args['filename'] ?? $this->getLogFile($channel);

                            $handler = $this->container->newInstance(
                                RotatingFileHandler::class,
                                $args
                            );
                            break;

                        default:
                            $handler = $this->container->newInstance($class, $args);
                    }
                }

                $handlers[] = $handler;
            }
        } else {
            $handlers = [$handler];
        }

        foreach ($handlers as $handler) {
            $handler->setFormatter(new LineFormatter(null, null, true));

            $logger->pushProcessor(new PsrLogMessageProcessor());

            // Basic string handler
            $logger->pushHandler($handler);
        }

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
     * @throws \Exception
     */
    public function createRotatingLogger($channel, $level = Logger::DEBUG, $maxFiles = 7): LoggerInterface
    {
        return $this->createLogger(
            $channel,
            $level,
            new RotatingFileHandler($this->getLogFile($channel), $maxFiles, $level)
        );
    }

    /**
     * getLogFile
     *
     * @param string $categoey
     *
     * @return  string
     */
    public function getLogFile($categoey): string
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
    public function hasLogger($channel): bool
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
    public function getLoggers(): array
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
    public function getNullLogger(): NullLogger
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
    #[\ReturnTypeWillChange]
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
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return $this->hasLogger($offset);
    }

    /**
     * Get a property.
     *
     * @param mixed $offset Offset key.
     *
     * @return  mixed The value to return.
     * @throws \Exception
     */
    #[\ReturnTypeWillChange]
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
    #[\ReturnTypeWillChange]
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
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        $this->removeLogger($offset);
    }

    /**
     * Count this object.
     *
     * @return  int
     */
    #[\ReturnTypeWillChange]
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
