<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule\Command;

use Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Manager\Logger;
use Windwalker\Core\Schedule\Schedule;
use Windwalker\Core\Schedule\ScheduleEvent;
use Windwalker\Core\Schedule\ScheduleService;
use Windwalker\Utilities\Arr;

/**
 * The ScheduleCommand class.
 *
 * @since  3.5.3
 */
#[CommandWrapper(description: 'Run CRON schedule')]
class ScheduleRunCommand implements CommandInterface
{
    /**
     * ScheduleCommand constructor.
     *
     * @param  ApplicationInterface  $app
     */
    public function __construct(protected ApplicationInterface $app, protected ScheduleService $scheduleService)
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
            'names',
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'The schedule name you want to run or test',
        );

        $command->addOption(
            'test',
            't',
            InputOption::VALUE_OPTIONAL,
            'Test schedules, will always match the time.',
            false
        );

        $command->addOption(
            'time',
            null,
            InputOption::VALUE_REQUIRED,
            'Simulate a date time.'
        );

        $command->addOption(
            'tz',
            null,
            InputOption::VALUE_REQUIRED,
            'The schedule timezone.'
        );

        $command->addOption(
            'tags',
            null,
            InputOption::VALUE_REQUIRED,
            'Tags to run schedule.'
        );
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  int Return 0 is success, 1-255 is failure.
     */
    public function execute(IOInterface $io): int
    {
        $schedule = $this->scheduleService->getSchedule();

        Logger::info('schedule', 'Run schedule');

        foreach ($this->getAvailableEvents($schedule, $io) as $event) {
            Logger::info('schedule', '  [Event] ' . $event->getName());

            try {
                $this->runEvent($event);
            } catch (\Throwable $e) {
                Logger::error(
                    'schedule',
                    sprintf(
                        "  [Error %s] %s - %s:%s",
                        $e->getCode(),
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getCode()
                    )
                );
            }
        }

        return 0;
    }

    protected function runEvent(ScheduleEvent $event): mixed
    {
        $handler = $event->getHandler();

        if (is_string($handler) && class_exists($handler)) {
            $handler = $this->app->make($handler);
        }

        return $this->app->call($handler);
    }

    protected function getAvailableEvents(Schedule $schedule, IOInterface $io): Generator
    {
        $tags = $io->getOption('tags') ?? '';
        $tags = Arr::explodeAndClear(',', $tags);

        if ($io->getOption('test') !== false) {
            $events = $schedule->getEvents($tags);
        } else {
            $tz = $io->getOption('tz');
            $time = $io->getOption('time') ?: 'now';

            $events = $schedule->getDueEvents($tags, $time, $tz);
        }

        $names = $io->getArgument('names');

        foreach ($events as $name => $event) {
            if ($names !== [] && !in_array($event->getName(), $names, true)) {
                continue;
            }

            yield $name => $event;
        }
    }
}
