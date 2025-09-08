<?php

declare(strict_types=1);

namespace Windwalker\Core\Queue\Command;

use Psr\Log\LogLevel;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Manager\Logger;
use Windwalker\DI\Exception\DefinitionNotFoundException;
use Windwalker\DI\Exception\DependencyResolutionException;
use Windwalker\Event\EventInterface;
use Windwalker\Queue\Enqueuer;
use Windwalker\Queue\Enqueuer\EnqueuerController;
use Windwalker\Queue\Event\AfterEnqueueEvent;
use Windwalker\Queue\Event\BeforeEnqueueEvent;
use Windwalker\Queue\Event\EnqueueFailureEvent;
use Windwalker\Queue\Event\LoopEndEvent;
use Windwalker\Queue\Event\LoopFailureEvent;
use Windwalker\Queue\Event\LoopStartEvent;
use Windwalker\Queue\Event\StopEvent;
use Windwalker\Queue\Queue;
use Windwalker\Queue\RunnerOptions;

trait EnqueuerCommandTrait
{
    /**
     * @param  ?string  $connection
     * @param  RunnerOptions  $options
     *
     * @return Enqueuer
     *
     * @throws DefinitionNotFoundException
     * @throws DependencyResolutionException
     */
    protected function createEnqueuer(?string $connection, RunnerOptions $options): Enqueuer
    {
        return new Enqueuer(
            queue: $this->app->retrieve(Queue::class, tag: $connection),
            options: $options,
            logger: $this->logger
        );
    }

    /**
     * @param  Enqueuer  $enqueuer
     *
     * @return  void
     */
    public function prepareEnqueuer(Enqueuer $enqueuer): void
    {
        $enqueuer->setInvoker($this->createEnqueuerInvoker());

        $handlerFiles = $this->app->config('queue.enqueuer.handlers') ?? [];
        $app = $this->app;

        foreach ($handlerFiles as $handlerFile) {
            include $handlerFile;
        }
    }

    public function createEnqueuerInvoker(): \Closure
    {
        return fn (EnqueuerController $controller, callable $invokable) => $this->app->call(
            $invokable,
            [
                $controller,
                'enqueuerController' => $controller,
                'controller' => $controller,
            ]
        );
    }

    protected function listenToEnqueuer(Enqueuer $enqueuer, IOInterface $io, string $connection): void
    {
        $enqueuer->on(
            BeforeEnqueueEvent::class,
            function (BeforeEnqueueEvent $event) use ($io) {
                $controller = $event->controller;

                if ($this->canShowLog(LogLevel::INFO)) {
                    $io->writeln("Enqueuing Start - Channel: <info>{$controller->channel}</info>.");
                }
            }
        )
            ->on(
                AfterEnqueueEvent::class,
                function (AfterEnqueueEvent $event) use ($io) {
                    $controller = $event->controller;

                    if ($this->canShowLog(LogLevel::INFO)) {
                        $io->writeln("  Enqueue End - Channel: <info>{$controller->channel}</info>.");
                    }
                }
            )
            ->on(
                EnqueueFailureEvent::class,
                function (EnqueueFailureEvent $event) use ($io) {
                    $controller = $event->controller;
                    $e = $event->exception;

                    Logger::error('enqueuer-error', $e);

                    $this->app->addMessage(
                        sprintf(
                            'Enqueue failed - Channel: <info>%s</info> - %s.',
                            $controller->channel,
                            $event->exception->getMessage(),
                        ),
                        'error'
                    );

                    if ($io->getOption('once')) {
                        throw $e;
                    }

                    $this->app->renderThrowable($e, $io->getOutput());
                }
            )
            ->on(
                LoopStartEvent::class,
                function (LoopStartEvent $event) {
                    /** @var Enqueuer $enqueuer */
                    $enqueuer = $event->runner;

                    if ($this->canShowLog(LogLevel::DEBUG)) {
                        $this->app->addMessage(
                            sprintf('Enqueuer loop step. State: <comment>%s</comment>', $enqueuer->getState()),
                            'debug'
                        );
                    }

                    switch ($enqueuer->getState()) {
                        case $enqueuer::STATE_ACTIVE:
                            if ($this->app->isMaintenance()) {
                                $enqueuer->setState($enqueuer::STATE_PAUSE);
                            }
                            break;

                        case $enqueuer::STATE_PAUSE:
                            if (!$this->app->isMaintenance()) {
                                $enqueuer->setState($enqueuer::STATE_ACTIVE);
                            }
                            break;
                    }
                }
            )
            ->on(
                LoopFailureEvent::class,
                function (LoopFailureEvent $event) use ($io) {
                    $e = $event->exception;

                    $this->app->addMessage(
                        sprintf(
                            '%s File: %s (%s)',
                            $e->getMessage(),
                            $e->getFile(),
                            $e->getLine()
                        ),
                        'error'
                    );

                    $this->app->renderThrowable($e, $io->getOutput());
                }
            )
            ->on(
                LoopEndEvent::class,
                function (LoopEndEvent $event) use ($connection, $io, $enqueuer) {
                    // Stop connections.
                    $this->runEnqueuerEndScripts('loop_end_scripts', $enqueuer, $event, $io, $connection);
                }
            )
            ->on(
                StopEvent::class,
                fn(StopEvent $event) => $io->writeln($event->reason)
            );
    }

    protected function runEnqueuerEndScripts(
        string $configName,
        Enqueuer $enqueuer,
        EventInterface $event,
        IOInterface $io,
        string $connection
    ): void {
        $scripts = $this->app->config('queue.enqueuer.' . $configName) ?? [];

        if (is_callable($scripts)) {
            $scripts = [$scripts];
        }

        foreach ($scripts as $script) {
            $this->app->call(
                $script,
                [
                    'enqueuer' => $enqueuer,
                    Enqueuer::class => $enqueuer,
                    'event' => $event,
                    $event::class => $event,
                    'io' => $io,
                    IOInterface::class => $io,
                    'connection' => $connection,
                ]
            );
        }
    }
}
