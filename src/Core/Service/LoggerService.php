<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Service;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Windwalker\Core\Manager\LoggerManager;

/**
 * The LoggerService class.
 *
 * @since  4.0.0-beta1
 */
class LoggerService
{
    /**
     * @var LoggerManager
     */
    protected LoggerManager $manager;

    /**
     * LoggerService constructor.
     *
     * @param  LoggerManager  $manager
     */
    public function __construct(LoggerManager $manager)
    {
        $this->manager = $manager;
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
    public function emergency(string|array $channel, string|array $message, array $context = []): static
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
    public function alert(string|array $channel, string|array $message, array $context = []): static
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
    public function critical(string|array $channel, string|array $message, array $context = []): static
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
    public function error(string|array $channel, string|array $message, array $context = []): static
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
    public function warning(string|array $channel, string|array $message, array $context = []): static
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
    public function notice(string|array $channel, string|array $message, array $context = []): static
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
    public function info(string|array $channel, string|array $message, array $context = []): static
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
    public function debug(string|array $channel, string|array $message, array $context = []): static
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
    public function log(string|array $channel, string|int $level, string|array $message, array $context = []): static
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

    public function getLogger(string $channel): LoggerInterface
    {
        return $this->manager->get($channel);
    }
}
