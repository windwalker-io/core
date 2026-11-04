<?php

declare(strict_types=1);

namespace Windwalker\Core\Queue;

use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\DI\Attributes\Service;
use Windwalker\Event\EventInterface;
use Windwalker\Queue\Enqueuer;
use Windwalker\Queue\Enqueuer\EnqueuerController;

#[Service]
class EnqueuerConfigurator
{
    public function __construct(protected ApplicationInterface $app) {}

    public function prepare(Enqueuer $enqueuer, ?callable $invoker = null): void
    {
        $enqueuer->setInvoker($invoker ?? $this->createInvoker());
        $app = $this->app;

        foreach ($app->config('queue.enqueuer.handlers') ?? [] as $handlerFile) {
            include $handlerFile;
        }
    }

    public function createInvoker(): \Closure
    {
        return fn(EnqueuerController $controller, callable $invokable) => $this->app->call(
            $invokable,
            [$controller, 'enqueuerController' => $controller, 'controller' => $controller]
        );
    }

    public function updateMaintenanceState(Enqueuer $enqueuer): void
    {
        if ($enqueuer->getState() === Enqueuer::STATE_ACTIVE && $this->app->isMaintenance()) {
            $enqueuer->setState(Enqueuer::STATE_PAUSE);
        } elseif ($enqueuer->getState() === Enqueuer::STATE_PAUSE && !$this->app->isMaintenance()) {
            $enqueuer->setState(Enqueuer::STATE_ACTIVE);
        }
    }

    public function runScripts(
        string $configName,
        Enqueuer $enqueuer,
        ?EventInterface $event,
        IOInterface $io,
        string $connection
    ): void {
        $scripts = $this->app->config('queue.enqueuer.' . $configName) ?? [];

        if (is_callable($scripts)) {
            $scripts = [$scripts];
        }

        foreach ($scripts as $script) {
            $this->app->call($script, [
                'enqueuer' => $enqueuer,
                Enqueuer::class => $enqueuer,
                'event' => $event,
                ...($event !== null ? [$event::class => $event] : []),
                'io' => $io,
                IOInterface::class => $io,
                'connection' => $connection,
            ]);
        }
    }
}
