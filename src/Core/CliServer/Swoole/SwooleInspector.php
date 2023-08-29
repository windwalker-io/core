<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\CliServer\Swoole;

use Swoole\Server;
use Windwalker\Core\CliServer\CliServerRuntime;
use Windwalker\Core\CliServer\CliServerState;
use Windwalker\Reactor\Swoole\SwooleServer;

/**
 * The SwooleInspector class.
 */
class SwooleInspector
{
    public function __construct(protected CliServerState $serverState)
    {
    }

    public function shouldEnableConnectionPool(): bool
    {
        return $this->serverState->getWorkerNumber() <= 1;
    }
}
