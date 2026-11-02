<?php

declare(strict_types=1);

namespace Windwalker\Core\CliServer\Swoole;

use Windwalker\Core\CliServer\CliServerInspectorInterface;
use Windwalker\Core\CliServer\CliServerState;

/**
 * The SwooleInspector class.
 */
class SwooleInspector implements CliServerInspectorInterface
{
    public function __construct(protected CliServerState $serverState) {}

    public function shouldEnableConnectionPool(): bool
    {
        return $this->serverState->getWorkerNumber() <= 1;
    }
}
