<?php

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Windwalker\Core\Service\LoggerService;

/**
 * The Logger class.
 *
 * @method static void log(string|array $channel, string|int $level, string|array $message, array $context = [])
 * @method static void emergency(string|array $channel, string|array $message, array $context = [])
 * @method static void alert(string|array $channel, string|array $message, array $context = [])
 * @method static void critical(string|array $channel, string|array $message, array $context = [])
 * @method static void error(string|array $channel, string|array $message, array $context = [])
 * @method static void warning(string|array $channel, string|array $message, array $context = [])
 * @method static void notice(string|array $channel, string|array $message, array $context = [])
 * @method static void info(string|array $channel, string|array $message, array $context = [])
 * @method static void debug(string|array $channel, string|array $message, array $context = [])
 */
class Logger
{
    public static \WeakReference|null $ref = null;

    public static function getInstance(): ?LoggerService
    {
        return static::$ref?->get();
    }

    public static function setInstance(LoggerService $loggerService): void
    {
        static::$ref = \WeakReference::create($loggerService);
    }

    public static function __callStatic(string $name, array $args): void
    {
        static::getInstance()?->$name(...$args);
    }
}
