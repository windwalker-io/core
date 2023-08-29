<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\CliServer\Swoole\Subscriber;

use Windwalker\Core\CliServer\CliServerRuntime;
use Windwalker\Reactor\Swoole\Event\StartEvent;

/**
 * The SyncServerStateListener class.
 */
class ServerStartedListener
{
    public function __invoke(StartEvent $event): void
    {
        $server = $event->getServer();
        $serv = $event->getSwooleServer();
        $serverState = CliServerRuntime::getServerState();

        $serverState->setMasterPid($serv->getMasterPid());
        $serverState->setManagerPid($serv->getManagerPid());
        $servers = $server->getServersInfo();
        $serverState->setServer(array_shift($servers));
        $serverState->setSubServers($servers);

        CliServerRuntime::saveServerState();
    }
}
