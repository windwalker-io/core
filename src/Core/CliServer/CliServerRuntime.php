<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\CliServer;

use Windwalker\Filesystem\FileObject;

/**
 * The CliServerRuntime class.
 */
class CliServerRuntime
{
    protected static CliServerStateManager $cliServerStateManager;

    public static function parseArgv(array $argv): CliServerState
    {
        $stateFilePath = $argv[1] ?? '';

        $cliServerStateManager = new CliServerStateManager($stateFilePath);

        static::$cliServerStateManager = $cliServerStateManager;

        return $cliServerStateManager->getState();
    }

    public static function getServerState(): CliServerState
    {
        return self::$cliServerStateManager->getState();
    }

    public static function saveServerState(CliServerState $state): FileObject
    {
        return self::$cliServerStateManager->writeState($state);
    }
}
