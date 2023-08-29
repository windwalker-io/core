<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\CliServer;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Windwalker\Core\CliServer\Swoole\SwooleInspector;
use Windwalker\Filesystem\FileObject;

/**
 * The CliServerRuntime class.
 */
class CliServerRuntime
{
    protected static SwooleInspector $inspector;

    protected static CliServerStateManager $cliServerStateManager;

    public static ConsoleOutput $output;

    protected static CliServerState $state;

    public static function boot(string $engine, array $argv): CliServerState
    {
        self::$state = self::parseArgv($argv);

        self::$inspector = new SwooleInspector(self::$state);

        return self::$state;
    }

    public static function parseArgv(array $argv): CliServerState
    {
        $stateFilePath = $argv[1] ?? '';

        $cliServerStateManager = new CliServerStateManager($stateFilePath);

        self::$cliServerStateManager = $cliServerStateManager;

        return $cliServerStateManager->getState();
    }

    public static function getServerState(): CliServerState
    {
        return self::$state ??= self::$cliServerStateManager->getState();
    }

    public static function saveServerState(?CliServerState $state = null): FileObject|false
    {
        if ($state) {
            static::$state = $state;
        }

        if (!self::$cliServerStateManager->getFilePath()) {
            return false;
        }

        return self::$cliServerStateManager->writeState(self::$state);
    }

    public static function log(string|iterable $message, bool $newline = false): void
    {
        self::getOutput()->write($message, $newline);
    }

    public static function logLine(string|iterable $message): void
    {
        self::getOutput()->writeln($message);
    }

    public static function logNewLine(int $lines): void
    {
        self::getOutput()->write(str_repeat("\n", $lines));
    }

    public static function getOutput(): ConsoleOutputInterface
    {
        return self::$output ??= new ConsoleOutput();
    }

    public static function getStyledOutput(): SymfonyStyle
    {
        return new SymfonyStyle(new ArrayInput([]), self::getOutput());
    }

    public static function getInspector(): SwooleInspector
    {
        return self::$inspector;
    }
}
