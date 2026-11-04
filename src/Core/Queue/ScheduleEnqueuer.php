<?php

declare(strict_types=1);

namespace Windwalker\Core\Queue;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Windwalker\Console\IO;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Events\Console\MessageOutputTrait;
use Windwalker\Core\Manager\Logger;
use Windwalker\DI\Attributes\Service;
use Windwalker\Queue\Enqueuer;
use Windwalker\Queue\Event\AfterEnqueueEvent;
use Windwalker\Queue\Event\BeforeEnqueueEvent;
use Windwalker\Queue\Event\DebugOutputEvent;
use Windwalker\Queue\Event\EnqueueFailureEvent;
use Windwalker\Queue\Event\LoopEndEvent;
use Windwalker\Queue\Event\LoopFailureEvent;
use Windwalker\Queue\Event\LoopStartEvent;
use Windwalker\Queue\Event\StopEvent;
use Windwalker\Queue\Queue;
use Windwalker\Queue\RunnerOptions;

#[Service]
class ScheduleEnqueuer
{
    use MessageOutputTrait;

    public function __construct(protected ApplicationInterface $app) {}

    public function run(
        int $lifetime,
        ?string $connection = null,
        array|string $channels = 'default',
        ScheduleEnqueuerOptions $options = new ScheduleEnqueuerOptions()
    ): void {
        $this->validateOptions($lifetime, $connection, $options);

        foreach ((array) $channels as $channel) {
            if (!is_string($channel) || $channel === '') {
                throw new InvalidArgumentException('Enqueuer channel names must be non-empty strings.');
            }
        }

        $connection ??= $this->app->config('queue.default');
        $runnerOptions = new RunnerOptions(
            once: $options->once,
            force: $options->force,
            memoryLimit: $options->memoryLimit,
            sleep: $options->sleep,
            timeout: $options->timeout,
            maxRuns: $options->maxRuns,
            lifetime: $lifetime,
            shuffleChannels: $options->shuffleChannels,
            stopWhenEmpty: $options->stopWhenEmpty,
            restartSignal: $this->app->path('@temp') . '/queue/enqueuer-restart'
        );
        $enqueuer = $this->createEnqueuer($connection, $runnerOptions);
        $configurator = new EnqueuerConfigurator($this->app);
        $configurator->prepare($enqueuer);
        $output = new class (fn($buffer) => $this->emitMessage($buffer, false)) extends Output {
            public function __construct(private \Closure $write)
            {
                parent::__construct();
            }

            protected function doWrite(string $message, bool $newline): void
            {
                ($this->write)($message . ($newline ? PHP_EOL : ''));
            }
        };
        $definition = new Command();
        $definition->addOption('once', null, InputOption::VALUE_NONE);
        $io = new IO(new ArrayInput(['--once' => $options->once], $definition->getDefinition()), $output, $definition);
        $enqueuer->addEventDealer($this->app->getEventDispatcher());
        $enqueuer->on(DebugOutputEvent::class, fn(DebugOutputEvent $event) => $this->emitMessage(
            sprintf('[%s] %s', $event->level, $event->message)
        ));
        $failure = null;
        $enqueuer->on(BeforeEnqueueEvent::class, function (BeforeEnqueueEvent $event) use (&$failure, $runnerOptions) {
            if ($failure !== null) {
                if ($runnerOptions->once) {
                    throw $failure;
                }

                // AbstractRunner exits the process for UnrecoverableException.
                throw new \RuntimeException('Enqueuer stopped after a producer failure.', previous: $failure);
            }

            $this->emitMessage("Enqueuing Start - Channel: <info>{$event->controller->channel}</info>.");
        });
        $enqueuer->on(AfterEnqueueEvent::class, fn(AfterEnqueueEvent $event) => $this->emitMessage(
            "Enqueue End - Channel: <info>{$event->controller->channel}</info>."
        ));
        $enqueuer->on(EnqueueFailureEvent::class, function (EnqueueFailureEvent $event) use (&$failure, $enqueuer) {
            $failure = $event->exception;
            Logger::error('system/enqueuer-error', $failure);
            $enqueuer->stop('Enqueue failed.');
            $this->emitErrorMessage($failure->getMessage());
        });
        $enqueuer->on(LoopFailureEvent::class, function (LoopFailureEvent $event) use (&$failure, $enqueuer) {
            if ($failure !== null) {
                return;
            }

            $failure = $event->exception;
            Logger::error('system/enqueuer-error', $failure);
            $enqueuer->stop('Enqueuer loop failed.');
            $this->emitErrorMessage($failure->getMessage());
        });
        $enqueuer->on(LoopStartEvent::class, static fn() => $configurator->updateMaintenanceState($enqueuer));
        $enqueuer->on(LoopEndEvent::class, static function (LoopEndEvent $event) use ($configurator, $enqueuer, $io, $connection) {
            $configurator->runScripts('loop_end_scripts', $enqueuer, $event, $io, $connection);
        });
        $enqueuer->on(StopEvent::class, fn(StopEvent $event) => $this->emitMessage($event->reason));
        $configurator->runScripts('init_scripts', $enqueuer, null, $io, $connection);

        $signals = [];
        $alarm = 0;
        $startedAt = hrtime(true);
        if (extension_loaded('pcntl') && !$runnerOptions->once) {
            foreach ([SIGINT, SIGTERM, SIGALRM] as $signal) {
                $signals[$signal] = pcntl_signal_get_handler($signal);
            }
            $alarm = pcntl_alarm(0);
        }

        try {
            if ($runnerOptions->once) {
                $enqueuer->next($channels);
            } else {
                $enqueuer->loop($channels);
            }
        } finally {
            if ($signals !== []) {
                pcntl_alarm(0);
                foreach ($signals as $signal => $handler) {
                    pcntl_signal($signal, $handler);
                }
                if ($alarm > 0) {
                    pcntl_alarm(max(1, $alarm - (int) ((hrtime(true) - $startedAt) / 1_000_000_000)));
                }
            }
        }

        if ($failure !== null) {
            throw $failure;
        }
    }

    protected function createEnqueuer(string $connection, RunnerOptions $options): Enqueuer
    {
        return new class (
            queue: $this->app->retrieve(Queue::class, tag: $connection),
            options: $options,
            logger: $this->app->retrieve(LoggerInterface::class, tag: 'system/enqueuer')
        ) extends Enqueuer {
            private ?array $shutdownHandlers = null;

            protected function registerSignals(): void
            {
                if (!extension_loaded('pcntl')) {
                    return;
                }

                $this->shutdownHandlers ??= [
                    SIGINT => pcntl_signal_get_handler(SIGINT),
                    SIGTERM => pcntl_signal_get_handler(SIGTERM),
                ];
                parent::registerSignals();

                foreach ($this->shutdownHandlers as $signal => $handler) {
                    pcntl_signal($signal, function (int $signal) use ($handler) {
                        $this->shutdown();

                        // Keep the enclosing scheduler's shutdown request alive.
                        if (is_callable($handler)) {
                            $handler($signal);
                        }
                    });
                }
            }
        };
    }

    protected function validateOptions(
        int $lifetime,
        ?string $connection,
        ScheduleEnqueuerOptions $options
    ): void {
        if (
            $lifetime < 0 || $options->memoryLimit < 0 || $options->timeout < 0
            || $options->maxRuns < 0 || !is_finite($options->sleep) || $options->sleep < 0
        ) {
            throw new InvalidArgumentException('Enqueuer limits and sleep must be finite and non-negative.');
        }

        if ($connection === '') {
            throw new InvalidArgumentException('Enqueuer connection must not be empty.');
        }
    }

    public function runByCommand(
        int $lifetime,
        ?string $connection = null,
        array|string|null $channels = null,
        ScheduleEnqueuerOptions $options = new ScheduleEnqueuerOptions()
    ): void {
        $this->validateOptions($lifetime, $connection, $options);

        $arguments = ['php', 'windwalker', 'queue:enqueuer'];

        foreach ((array) $channels as $channel) {
            if (!is_string($channel) || $channel === '' || str_starts_with($channel, '-')) {
                throw new InvalidArgumentException('Enqueuer channel names must be non-empty strings without a leading hyphen.');
            }

            $arguments[] = escapeshellarg($channel);
        }

        $cliOptions = [
            '--lifetime' => $lifetime,
            '--memory' => $options->memoryLimit,
            '--sleep' => $options->sleep,
            '--timeout' => $options->timeout,
            '--max-runs' => $options->maxRuns,
        ];

        if ($connection !== null) {
            $cliOptions['--connection'] = $connection;
        }

        foreach ($cliOptions as $name => $value) {
            $arguments[] = $name;
            $arguments[] = escapeshellarg((string) $value);
        }

        foreach ([
            '--once' => $options->once,
            '--force' => $options->force,
            '--shuffle-channels' => $options->shuffleChannels,
            '--stop-when-empty' => $options->stopWhenEmpty,
        ] as $name => $enabled) {
            if ($enabled) {
                $arguments[] = $name;
            }
        }

        $this->app->mustRunProcess(
            implode(' ', $arguments),
            output: function ($type, $buffer) {
                $this->emitMessage($buffer, false);
            }
        );
    }
}
