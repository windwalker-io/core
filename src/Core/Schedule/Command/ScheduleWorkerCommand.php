<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule\Command;

use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Database\DatabaseConnectionRecovery;
use Windwalker\Core\Events\Console\ErrorMessageOutputEvent;
use Windwalker\Core\Events\Console\MessageOutputEvent;
use Windwalker\Core\Schedule\Event\AfterScheduleRunEvent;
use Windwalker\Core\Schedule\Event\ScheduleSkippedEvent;
use Windwalker\Core\Schedule\Event\ScheduleFailureEvent;
use Windwalker\Core\Schedule\Event\LoopStartEvent;
use Windwalker\Core\Schedule\Event\StartEvent;
use Windwalker\Core\Schedule\Event\StopEvent;
use Windwalker\Core\Schedule\SchedulerRunner;
use Windwalker\Core\Schedule\SchedulerOptions;
use Windwalker\Utilities\Arr;

#[CommandWrapper(description: 'Run a resident CRON schedule worker.')]
class ScheduleWorkerCommand implements CommandInterface, SignalableCommandInterface
{
    public function __construct(protected SchedulerRunner $runner, protected ApplicationInterface $app) {}

    public function configure(Command $command): void
    {
        $command->addArgument('names', InputArgument::IS_ARRAY, 'The schedule names to run.');
        $command->addOption('tags', null, InputOption::VALUE_REQUIRED, 'Comma-separated schedule tags.');
        $command->addOption('tz', null, InputOption::VALUE_REQUIRED, 'The schedule timezone.');
        $command->addOption('once', 'o', InputOption::VALUE_NONE, 'Run due schedules once and exit.');
        $command->addOption(
            'lifetime',
            'L',
            InputOption::VALUE_REQUIRED,
            'Maximum worker lifetime in seconds; 0 means unlimited.',
            '0'
        );
        $command->addOption(
            'max-runs',
            null,
            InputOption::VALUE_REQUIRED,
            'Maximum minute ticks (including empty ticks); 0 means unlimited.',
            '0'
        );
    }

    public function execute(IOInterface $io): int
    {
        $options = $this->getRunnerOptions($io);

        $this->runner->on(
            StartEvent::class,
            function () use ($io, $options) {
                $io->writeln(
                    sprintf(
                        'Schedule worker started. Press <info>Ctrl+C</info> to stop. PID: <comment>%s</comment>',
                        getmypid()
                    )
                );
                $this->displayInfo($io, $options);
            }
        );
        $this->runner->onMessages(
            static function (MessageOutputEvent $event) use ($io) {
                if (!$event instanceof ErrorMessageOutputEvent) {
                    $event->options |= OutputInterface::VERBOSITY_VERBOSE;
                }

                $event->writeWith($io->getOutput());
            }
        );
        // Dealers are weakly referenced; keep this adapter alive throughout run().
        $eventDealer = new class ($this->app->getEventDispatcher()) implements EventDispatcherInterface {
            public function __construct(private EventDispatcherInterface $dispatcher) {}

            public function dispatch(object $event): object
            {
                // ConsoleApplication's message output may differ from this command's output.
                return $event instanceof MessageOutputEvent ? $event : $this->dispatcher->dispatch($event);
            }
        };
        $this->runner->addEventDealer($eventDealer);
        $this->runner->on(
            AfterScheduleRunEvent::class,
            static function (AfterScheduleRunEvent $event) use ($io) {
                $io->writeln(
                    sprintf('Schedule completed: %s (%.3fs)', $event->scheduleEvent->getName(), $event->duration),
                    OutputInterface::VERBOSITY_VERBOSE
                );
            }
        );
        $this->runner->on(
            ScheduleSkippedEvent::class,
            static function (ScheduleSkippedEvent $event) use ($io) {
                $io->writeln(
                    sprintf(
                        'Schedule skipped: %s (%s)',
                        OutputFormatter::escape($event->scheduleEvent->getName()),
                        $event->reason->value
                    ),
                    OutputInterface::VERBOSITY_VERBOSE
                );
            }
        );
        $this->runner->on(
            LoopStartEvent::class,
            static function (LoopStartEvent $event) use ($io) {
                $io->writeln(
                    sprintf('[%s] Schedule tick #%d', $event->time->format('c'), $event->run),
                    OutputInterface::VERBOSITY_VERY_VERBOSE
                );
            }
        );
        $this->runner->on(
            StopEvent::class,
            static function (StopEvent $event) use ($io) {
                $io->writeln(
                    sprintf('Schedule worker stopped: %s (%d ticks)', $event->reason, $event->runs),
                    OutputInterface::VERBOSITY_VERY_VERBOSE
                );
            }
        );
        $this->runner->on(
            ScheduleFailureEvent::class,
            fn(ScheduleFailureEvent $event) => $this->shutdownOnDatabaseFailure($event->exception, $io)
        );

        try {
            $this->runner->run($options);
        } catch (\Throwable $e) {
            $this->shutdownOnDatabaseFailure($e, $io);

            throw $e;
        }

        return 0;
    }

    protected function shutdownOnDatabaseFailure(\Throwable $exception, IOInterface $io): void
    {
        if ($io->getOption('once') || !DatabaseConnectionRecovery::isConnectionFailure($exception)) {
            return;
        }

        $io->writeln('<comment>' . DatabaseConnectionRecovery::MESSAGE . '</comment>');
        $this->app->service(DatabaseConnectionRecovery::class)->shutdown($exception);
    }

    protected function getRunnerOptions(IOInterface $io): SchedulerOptions
    {
        return new SchedulerOptions(
            lifetime: $this->getNonNegativeInteger($io, 'lifetime'),
            names: $io->getArgument('names'),
            tags: Arr::explodeAndClear(',', $io->getOption('tags') ?? ''),
            timezone: $io->getOption('tz'),
            once: $io->getOption('once'),
            maxRuns: $this->getNonNegativeInteger($io, 'max-runs')
        );
    }

    protected function displayInfo(IOInterface $io, SchedulerOptions $options): void
    {
        $names = $options->names;
        $tags = $options->tags;
        $timezone = $options->timezone;
        $timezone = $timezone instanceof \DateTimeZone ? $timezone->getName() : $timezone;

        $info1 = [
            'Names: <info>' . OutputFormatter::escape($names ? implode(', ', $names) : 'all') . '</info>',
            'Tags: <info>' . OutputFormatter::escape($tags ? implode(', ', $tags) : 'all') . '</info>',
            'Timezone: <info>' . OutputFormatter::escape($timezone ?? date_default_timezone_get()) . '</info>',
            'Sleep: <info>1s</info>',
        ];
        $info2 = [
            'Max runs: <info>' . ($options->maxRuns ? (string) $options->maxRuns : 'unlimited') . '</info>',
            'Max lifetime: <info>' . ($options->lifetime ? $options->lifetime . 's' : 'unlimited') . '</info>',
            'Once: ' . ($options->once ? '<info>yes</info>' : '<comment>no</comment>'),
            'Schedule interval: <info>1 minute</info>',
        ];

        $io->style()->createTable()
            ->setRows([$info1, $info2])
            ->render();
    }

    protected function getNonNegativeInteger(IOInterface $io, string $name): int
    {
        $value = filter_var($io->getOption($name), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

        if ($value === false) {
            throw new InvalidArgumentException(sprintf('--%s must be a non-negative integer.', $name));
        }

        return $value;
    }

    public function getSubscribedSignals(): array
    {
        return extension_loaded('pcntl') ? [SIGINT, SIGTERM] : [];
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        $this->runner->stop();

        return false;
    }
}
