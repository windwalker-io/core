<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\CliServer\Swoole;

use Windwalker\Core\CliServer\CliServerRuntime;
use Windwalker\DI\Container;
use Windwalker\Event\Attributes\EventSubscriber;
use Windwalker\Event\Attributes\ListenTo;
use Windwalker\Reactor\Swoole\Event\TaskEvent;
use Windwalker\Reactor\Swoole\Event\WorkerStartEvent;

/**
 * The SwooleWorkerSubscriber class.
 */
#[EventSubscriber]
class SwooleWorkerSubscriber
{
    public function __construct(protected Container $container)
    {
    }

    #[ListenTo(WorkerStartEvent::class)]
    public function workerStart(WorkerStartEvent $event): void
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $state = CliServerRuntime::getServerState();

            $workerId = $event->getWorkerId();

            $isTask = $workerId > $state->getWorkerNumber();
            $name = $isTask ? 'task' : 'worker';

            cli_set_process_title("swoole: {$name} - {$state->getName()}:{$state->getServerName()}");
        }
    }

    #[ListenTo(TaskEvent::class)]
    public function task(TaskEvent $event): void
    {
        $data = $event->getData();

        if (is_callable($data)) {
            $this->container->call($data);
        } else {
            throw new \RuntimeException('Currently task data must be a callable');
        }
    }
}
