<?php

declare(strict_types=1);

namespace Windwalker\Core\Queue;

use Psr\Log\LoggerInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Events\Console\MessageOutputTrait;
use Windwalker\Core\Manager\Logger;
use Windwalker\DI\Attributes\Service;
use Windwalker\Event\EventInterface;
use Windwalker\Queue\Event\AfterJobRunEvent;
use Windwalker\Queue\Event\BeforeJobRunEvent;
use Windwalker\Queue\Event\DebugOutputEvent;
use Windwalker\Queue\Event\JobFailureEvent;
use Windwalker\Queue\Event\LoopEndEvent;
use Windwalker\Queue\Event\LoopFailureEvent;
use Windwalker\Queue\Event\LoopStartEvent;
use Windwalker\Queue\Event\StopEvent;
use Windwalker\Queue\Failer\QueueFailerInterface;
use Windwalker\Queue\Job\JobController;
use Windwalker\Queue\Queue;
use Windwalker\Queue\RunnerOptions;
use Windwalker\Queue\Worker;

#[Service]
class ScheduleWorker
{
    use MessageOutputTrait;

    public function __construct(protected ApplicationInterface $app)
    {
    }

    public function runByCommand(
        int $lifetime,
        ?string $connection = null,
        array|string|null $channels = null,
        ScheduleWorkerOptions $options = new ScheduleWorkerOptions()
    ): void {
        $cliOptions = [];
        $cliArgs = [];

        if ($lifetime > 0) {
            $cliOptions['--lifetime'] = $lifetime;
        }

        if ($connection) {
            $cliOptions['--connection'] = $connection;
        }

        if ($options->once) {
            $cliOptions['--once'] = true;
        }

        if ($options->backoff) {
            $cliOptions['--backoff'] = $options->backoff;
        }

        if ($options->timeout) {
            $cliOptions['--timeout'] = $options->timeout;
        }

        if ($options->tries) {
            $cliOptions['--tries'] = $options->tries;
        }

        if ($options->sleep) {
            $cliOptions['--sleep'] = $options->sleep;
        }

        if ($options->force) {
            $cliOptions['--force'] = true;
        }

        if ($options->maxRuns) {
            $cliOptions['--max-runs'] = $options->maxRuns;
        }

        if ($options->stopWhenEmpty) {
            $cliOptions['--stop-when-empty'] = true;
        }

        if ($options->enqueuer) {
            $cliOptions['--enqueuer'] = true;
        }

        if ($channels) {
            $cliArgs = (array) $channels;
        }

        $cmd = 'php windwalker queue:worker ';

        foreach ($cliArgs as $cliArg) {
            $cmd .= escapeshellarg($cliArg) . ' ';
        }

        foreach ($cliOptions as $key => $value) {
            if (is_bool($value)) {
                $cmd .= $key . ' ';
            } else {
                $cmd .= sprintf('%s %s ', $key, escapeshellarg((string) $value));
            }
        }

        $this->app->mustRunProcess(
            $cmd,
            output: function ($type, $buffer) {
                $this->emitMessage($buffer, false);
            }
        );
    }

    public function runOnce(
        ?string $connection = null,
        array|string $channels = 'default',
        ScheduleWorkerOptions $options = new ScheduleWorkerOptions()
    ): void {
        $options->once = true;

        $this->run(
            lifetime: 0,
            connection: $connection,
            channels: $channels,
            options: $options
        );
    }

    public function run(
        int $lifetime,
        ?string $connection = null,
        array|string $channels = 'default',
        ScheduleWorkerOptions $options = new ScheduleWorkerOptions()
    ): void {
        if (!class_exists(Worker::class)) {
            throw new \DomainException('Please install windwalker/queue first.');
        }

        $options->lifetime = $lifetime;
        $connection ??= $this->app->config('queue.default');

        $worker = $this->createWorker($connection, $options);
        $worker->setInvoker($this->createInvoker());

        $worker->on(
            DebugOutputEvent::class,
            function (DebugOutputEvent $event) {
                $this->emitMessage(
                    sprintf(
                        '[%s] %s %s',
                        $event->level,
                        $event->message,
                        $event->context ? json_encode($event->context) : ''
                    )
                );
            }
        );

        $this->listenToWorker($worker, $connection, $options->once);

        $this->runConfigScripts(
            'init_scripts',
            $worker,
            null,
            $connection
        );

        $worker->loop($channels);
    }

    protected function listenToWorker(Worker $worker, string $connection, bool $once = false): void
    {
        $worker->on(
            BeforeJobRunEvent::class,
            function (BeforeJobRunEvent $event) {
                $this->emitMessage(
                    sprintf(
                        'Run Job: <info>%s</info> - Message ID: <info>%s</info>',
                        get_debug_type($event->job),
                        $event->message->getId()
                    )
                );
            }
        )
            ->on(
                AfterJobRunEvent::class,
                function (AfterJobRunEvent $event) use ($connection, $worker) {
                    $controller = $event->controller;

                    if ($controller->defer) {
                        $this->emitMessage(
                            sprintf(
                                'Job Message: <info>%s</info> %s',
                                $event->message->getId(),
                                $controller->defer->getReasonText()
                            )
                        );
                    } elseif ($controller->abandoned) {
                        $this->emitMessage(
                            sprintf(
                                'Job Message: <info>%s</info> END - %s',
                                $event->message->getId(),
                                $controller->abandoned->toReasonText()
                            )
                        );
                    } else {
                        $this->emitMessage(
                            sprintf(
                                'Job Message: <info>%s</info> END',
                                $event->message->getId()
                            )
                        );
                    }

                    $this->runConfigScripts(
                        'job_end_scripts',
                        $worker,
                        $event,
                        $connection
                    );
                }
            )
            ->on(
                JobFailureEvent::class,
                function (JobFailureEvent $event) use ($once, $connection) {
                    $controller = $event->controller;
                    $message = $controller->message;
                    $e = $event->exception;
                    $backoff = $event->backoff;

                    Logger::error('queue-error', $e);

                    if ($controller->shouldDelete) {
                        $this->app->addMessage(
                            sprintf(
                                'Job %s failed - ID: <info>%s</info> - %s. %s, will not retry.',
                                get_debug_type($event->job),
                                $message->getId(),
                                $event->exception->getMessage(),
                                $controller->maxAttemptsExceeds
                                    ? 'Max attempts exceeded'
                                    : $controller->abandoned->toReasonText()
                            ),
                            'error'
                        );
                    } else {
                        $this->app->addMessage(
                            sprintf(
                                'Job %s failed - ID: <info>%s</info> - %s. Will retry after %d seconds.',
                                get_debug_type($event->job),
                                $message->getId(),
                                $event->exception->getMessage(),
                                $backoff
                            ),
                            'error'
                        );
                    }

                    if (!$controller->abandoned && $message->isDeleted()) {
                        $this->app->service(QueueFailerInterface::class)
                            ->add(
                                $connection,
                                $message->getChannel(),
                                json_encode($message),
                                (string) $event->exception
                            );
                    }

                    if ($once) {
                        throw $e;
                    }

                    $this->emitMessage((string) $e);
                }
            )
            ->on(
                LoopStartEvent::class,
                function (LoopStartEvent $event) {
                    $worker = $event->runner;

                    switch ($worker->getState()) {
                        case $worker::STATE_ACTIVE:
                            if ($this->app->isMaintenance()) {
                                $worker->setState($worker::STATE_PAUSE);
                            }
                            break;

                        case $worker::STATE_PAUSE:
                            if (!$this->app->isMaintenance()) {
                                $worker->setState($worker::STATE_ACTIVE);
                            }
                            break;
                    }
                }
            )
            ->on(
                LoopFailureEvent::class,
                function (LoopFailureEvent $event) {
                    $e = $event->exception;

                    $this->emitMessage(
                        sprintf(
                            '%s File: %s (%s)',
                            $e->getMessage(),
                            $e->getFile(),
                            $e->getLine()
                        ),
                    );

                    $this->emitMessage((string) $e);
                }
            )
            ->on(
                LoopEndEvent::class,
                function (LoopEndEvent $event) use ($connection, $worker) {
                    // Stop connections.
                    $this->runConfigScripts('loop_end_scripts', $worker, $event, $connection);
                }
            )
            ->on(
                StopEvent::class,
                fn(StopEvent $event) => $this->emitMessage($event->reason)
            );
    }

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

    protected function createWorker(?string $connection, RunnerOptions $options): Worker
    {
        return new Worker(
            queue: $this->app->retrieve(Queue::class, tag: $connection),
            options: $options,
            logger: $this->createLogger()
        );
    }

    protected function createLogger(): LoggerInterface
    {
        return $this->app->service(LoggerInterface::class, tag: 'system/cron-queue');
    }

    protected function runConfigScripts(
        string $configName,
        Worker $worker,
        ?EventInterface $event,
        string $connection
    ): void {
        $scripts = $this->app->config('queue.' . $configName) ?? [];

        if (is_callable($scripts)) {
            $scripts = [$scripts];
        }

        foreach ($scripts as $script) {
            $this->app->call(
                $script,
                [
                    'worker' => $worker,
                    Worker::class => $worker,
                    'event' => $event,
                    $event::class => $event,
                    'connection' => $connection,
                ]
            );
        }
    }
}
