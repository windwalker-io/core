<?php

declare(strict_types=1);

namespace Windwalker\Core\Queue;

use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\DI\Attributes\Service;
use Windwalker\Event\EventInterface;
use Windwalker\Queue\Job\JobController;
use Windwalker\Queue\Worker;

#[Service]
class WorkerConfigurator
{
    public function __construct(protected ApplicationInterface $app) {}

    public function createInvoker(): \Closure
    {
        return fn(JobController $controller, callable $invokable) => $this->app->call(
            $invokable,
            [
                'jobController' => $controller,
                'controller' => $controller,
                JobController::class => $controller,
            ]
        );
    }

    public function updateMaintenanceState(Worker $worker): void
    {
        if ($worker->getState() === Worker::STATE_ACTIVE && $this->app->isMaintenance()) {
            $worker->setState(Worker::STATE_PAUSE);
        } elseif ($worker->getState() === Worker::STATE_PAUSE && !$this->app->isMaintenance()) {
            $worker->setState(Worker::STATE_ACTIVE);
        }
    }

    public function runScripts(
        string $configName,
        Worker $worker,
        ?EventInterface $event,
        string $connection,
        ?IOInterface $io = null
    ): void {
        $scripts = $this->app->config('queue.' . $configName) ?? [];

        if (is_callable($scripts)) {
            $scripts = [$scripts];
        }

        foreach ($scripts as $script) {
            $this->app->call($script, [
                'worker' => $worker,
                Worker::class => $worker,
                'event' => $event,
                ...($event !== null ? [$event::class => $event] : []),
                ...($io !== null ? ['io' => $io, IOInterface::class => $io] : []),
                'connection' => $connection,
            ]);
        }
    }
}
