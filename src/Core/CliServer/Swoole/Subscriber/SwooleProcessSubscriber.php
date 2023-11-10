<?php

declare(strict_types=1);

namespace Windwalker\Core\CliServer\Swoole\Subscriber;

use Windwalker\Core\CliServer\CliServerRuntime;
use Windwalker\DI\Container;
use Windwalker\Event\Attributes\EventSubscriber;
use Windwalker\Event\Attributes\ListenTo;
use Windwalker\Http\Event\ResponseEvent;
use Windwalker\Reactor\Swoole\Event\ManagerStartEvent;
use Windwalker\Reactor\Swoole\Event\TaskEvent;
use Windwalker\Reactor\Swoole\Event\WorkerStartEvent;

/**
 * The SwooleWorkerSubscriber class.
 */
#[EventSubscriber]
class SwooleProcessSubscriber
{
    public function __construct(protected Container $container)
    {
    }

    #[ListenTo(ManagerStartEvent::class)]
    public function managerStart(ManagerStartEvent $event): void
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $state = CliServerRuntime::getServerState();

            $name = 'manager';

            cli_set_process_title("swoole: {$name} - {$state->getName()}:{$state->getServerName()}");
        }
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

    #[ListenTo(ResponseEvent::class)]
    public function response(): void
    {
        // Todo: Move this to config
        $garbageMax = 50;
        if ((memory_get_usage() / 1024 / 1024) > $garbageMax) {
            gc_collect_cycles();
        }
    }
}
