<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\CliServer;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Windwalker\Filesystem\FileObject;

/**
 * The CliServerRuntime class.
 */
class CliServerRuntime
{
    protected static string $stateFilePath = '';

    protected static CliServerStateManager $cliServerStateManager;

    public static ConsoleOutput $output;

    public static function parseArgv(array $argv): CliServerState
    {
        self::$stateFilePath = $stateFilePath = $argv[1] ?? '';

        $cliServerStateManager = new CliServerStateManager($stateFilePath);

        static::$cliServerStateManager = $cliServerStateManager;

        return $cliServerStateManager->getState();
    }

    public static function getServerState(): CliServerState
    {
        return self::$cliServerStateManager->getState();
    }

    public static function saveServerState(CliServerState $state): FileObject|false
    {
        if (!self::$cliServerStateManager->getFilePath()) {
            return false;
        }

        return self::$cliServerStateManager->writeState($state);
    }

    public static function log(string|iterable $message, bool $newline = false): void
    {
        static::getOutput()->write($message, $newline);
    }

    public static function logLine(string|iterable $message): void
    {
        static::getOutput()->writeln($message);
    }

    public static function logNewLine(int $lines): void
    {
        static::getOutput()->write(str_repeat("\n", $lines));
    }

    public static function getOutput(): ConsoleOutputInterface
    {
        return static::$output ??= new ConsoleOutput();
    }
}
