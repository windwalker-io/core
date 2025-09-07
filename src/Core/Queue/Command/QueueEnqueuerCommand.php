<?php

declare(strict_types=1);

namespace Windwalker\Core\Queue\Command;

use DomainException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Core\Manager\Logger;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\DI\Exception\DefinitionNotFoundException;
use Windwalker\DI\Exception\DependencyResolutionException;
use Windwalker\Event\EventInterface;
use Windwalker\Queue\Enqueuer;
use Windwalker\Queue\Event\AfterEnqueueEvent;
use Windwalker\Queue\Event\BeforeEnqueueEvent;
use Windwalker\Queue\Event\EnqueueFailureEvent;
use Windwalker\Queue\Event\LoopEndEvent;
use Windwalker\Queue\Event\LoopFailureEvent;
use Windwalker\Queue\Event\LoopStartEvent;
use Windwalker\Queue\Event\StopEvent;
use Windwalker\Queue\Queue;
use Windwalker\Queue\RunnerOptions;
use Windwalker\Queue\Worker;

/**
 * The QueueWorkerCommand class.
 */
#[CommandWrapper(
    description: 'Start an enqueuer.'
)]
class QueueEnqueuerCommand implements CommandInterface
{
    use QueueCommandTrait;

    protected IOInterface $io;

    public function __construct(protected ConsoleApplication $app)
    {
    }

    /**
     * configure
     *
     * @param  Command  $command
     *
     * @return  void
     */
    public function configure(Command $command): void
    {
        $command->addArgument(
            'channels',
            InputArgument::IS_ARRAY,
            'The channel name to run.'
        );

        $command->addOption(
            'connection',
            'c',
            InputOption::VALUE_REQUIRED,
            'The connection of queue.'
        );

        $command->addOption(
            'once',
            'o',
            InputOption::VALUE_NONE,
            'Only run next job.'
        );

        $command->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force run worker if in pause mode.'
        );

        $command->addOption(
            'memory',
            'm',
            InputOption::VALUE_REQUIRED,
            'The memory limit in megabytes.',
            '128'
        );

        $command->addOption(
            'sleep',
            's',
            InputOption::VALUE_REQUIRED,
            'Number of seconds to sleep after job run complete.',
            '1'
        );

        $command->addOption(
            'timeout',
            null,
            InputOption::VALUE_REQUIRED,
            'Number of seconds that a job can run.',
            '60'
        );

        $command->addOption(
            'max-runs',
            null,
            InputOption::VALUE_REQUIRED,
            'The max times to run the worker before exit. 0 for unlimited.',
            '0'
        );

        $command->addOption(
            'lifetime',
            null,
            InputOption::VALUE_REQUIRED,
            'The max seconds to run the worker before exit. 0 for unlimited.',
            '0'
        );

        $command->addOption(
            'stop-when-empty',
            'e',
            InputOption::VALUE_NONE,
            'Stop the worker when no job is remaining.',
        );
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  int Return 0 is success, 1-255 is failure.
     * @throws DefinitionException
     */
    public function execute(IOInterface $io): int
    {
        if (!class_exists(Worker::class)) {
            throw new DomainException('Please install windwalker/queue first.');
        }

        $this->io = $io;

        $channels = $io->getArgument('channels') ?: 'default';
        $options = $this->getWorkOptions($io);
        $connection = $io->getOption('connection') ?: $this->app->config('queue.default');

        $enqueuer = $this->createEnqueuer($connection, $options);
        $this->prepareEnqueuer($enqueuer);

        $this->prepareDebugServices($io, $enqueuer);

        $enqueuer->addEventDealer($this->app);

        $this->listenToRunner($enqueuer, $io, $connection);

        // Show enqueuer start information
        $io->writeln(
            sprintf(
                "Enqueuer started. Press <info>Ctrl+C</info> to stop. PID: <comment>%s</comment>",
                getmypid()
            )
        );
        $this->displayInfo($connection, $channels, $options);

        if ($io->getOption('once')) {
            $enqueuer->getEventDispatcher()->on(
                EnqueueFailureEvent::class,
                function (EnqueueFailureEvent $event) {
                    $code = $event->exception->getCode();

                    exit($code === 0 ? 1 : $code);
                }
            );

            $enqueuer->next($channels);
        } else {
            $enqueuer->loop($channels);
        }

        return 0;
    }

    protected function getWorkOptions(IOInterface $io): RunnerOptions
    {
        return new RunnerOptions(
            once: (bool) $io->getOption('once'),
            // backoff: (int) ($io->getOption('backoff') ?? $io->getOption('delay')),
            force: (bool) $io->getOption('force'),
            memoryLimit: (int) $io->getOption('memory'),
            sleep: (float) $io->getOption('sleep'),
            // tries: (int) $io->getOption('tries'),
            timeout: (int) $io->getOption('timeout'),
            maxRuns: (int) $io->getOption('max-runs'),
            lifetime: (int) $io->getOption('lifetime'),
            stopWhenEmpty: (bool) $io->getOption('stop-when-empty'),
            restartSignal: $this->app->path('@temp') . '/queue/enqueuer-restart',
        );
    }

    public function invoke(Enqueuer\EnqueuerController $controller, callable $invokable): mixed
    {
        return $this->app->call(
            $invokable,
            [
                $controller,
                'enqueuerController' => $controller,
                'controller' => $controller,
            ]
        );
    }

    protected function listenToRunner(Enqueuer $enqueuer, IOInterface $io, string $connection): void
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
                    $this->runEndScripts('loop_end_scripts', $enqueuer, $event, $io, $connection);
                }
            )
            ->on(
                StopEvent::class,
                fn(StopEvent $event) => $io->writeln($event->reason)
            );
    }

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
        $enqueuer->setInvoker($this->invoke(...));

        $enqueuerConfig = $this->app->config('queue.enqueuer');
        $enqueuer->setDefaultHandler($enqueuerConfig['default_handler'] ?? null);

        foreach ((array) ($enqueuerConfig['channel_handlers'] ?? []) as $channel => $handler) {
            $enqueuer->addChannelHandler($channel, $handler);
        }
    }

    protected function runEndScripts(
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

    protected function createLogger(): LoggerInterface
    {
        return $this->app->retrieve(LoggerInterface::class, tag: 'system/enqueuer');
    }
}
