<?php

declare(strict_types=1);

namespace Windwalker\Core\Service;

use Exception;
use Psr\Log\LoggerInterface;

interface CoreLoggerInterface
{
    /**
     * System is unusable.
     *
     * @param  string|array  $channel
     * @param  mixed         $message
     * @param  array         $context
     *
     * @return  static
     * @throws  Exception
     */
    public function emergency(string|array $channel, mixed $message, array $context = []): static;

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param  string|array  $channel
     * @param  mixed         $message
     * @param  array         $context
     *
     * @return  static
     * @throws  Exception
     */
    public function alert(string|array $channel, mixed $message, array $context = []): static;

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param  string|array  $channel
     * @param  mixed         $message
     * @param  array         $context
     *
     * @return  static
     * @throws  Exception
     */
    public function critical(string|array $channel, mixed $message, array $context = []): static;

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param  string|array  $channel
     * @param  mixed         $message
     * @param  array         $context
     *
     * @return  static
     * @throws  Exception
     */
    public function error(string|array $channel, mixed $message, array $context = []): static;

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param  string|array  $channel
     * @param  mixed         $message
     * @param  array         $context
     *
     * @return  static
     * @throws  Exception
     */
    public function warning(string|array $channel, mixed $message, array $context = []): static;

    /**
     * Normal but significant events.
     *
     * @param  string|array  $channel
     * @param  mixed         $message
     * @param  array         $context
     *
     * @return  static
     * @throws  Exception
     */
    public function notice(string|array $channel, mixed $message, array $context = []): static;

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param  string|array  $channel
     * @param  mixed         $message
     * @param  array         $context
     *
     * @return  static
     * @throws  Exception
     */
    public function info(string|array $channel, mixed $message, array $context = []): static;

    /**
     * Detailed debug information.
     *
     * @param  string|array  $channel
     * @param  mixed         $message
     * @param  array         $context
     *
     * @return  static
     * @throws  Exception
     */
    public function debug(string|array $channel, mixed $message, array $context = []): static;

    /**
     * Logs with an arbitrary level.
     *
     * @param  string|array  $channel
     * @param  string|int    $level
     * @param  mixed         $message
     * @param  array         $context
     *
     * @return  static
     * @throws  Exception
     */
    public function log(string|array $channel, string|int $level, mixed $message, array $context = []): static;

    public function getGlobalContext(): array;

    /**
     * Replace the global context.
     */
    public function setGlobalContext(array $context): static;

    /**
     * Merge data into the global context.
     */
    public function addGlobalContext(array $context): static;

    public function resetGlobalContext(): static;

    public function getLogger(string $channel): LoggerInterface;
}
