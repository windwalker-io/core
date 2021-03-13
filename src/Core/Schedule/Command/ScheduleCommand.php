<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 .
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Schedule\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Console\CoreCommand;
use Windwalker\Core\Schedule\Schedule;
use Windwalker\Core\Schedule\ScheduleConsoleInterface;

/**
 * The ScheduleCommand class.
 *
 * @since  3.5.3
 */
#[CommandWrapper(description: 'Run CRON schedule')]
class ScheduleCommand implements CommandInterface
{
    /**
     * ScheduleCommand constructor.
     *
     * @param  ApplicationInterface  $app
     */
    public function __construct(protected ApplicationInterface $app)
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
        $routes = (array) $this->app->config('schedules');

        $schedule = $this->app->make(Schedule::class);

        foreach ($routes as $route) {
            $this->loadScheduleTasks($schedule, $route);
        }

        if ($io->getOption('test') !== false) {
            $events = $schedule->getEvents();
        } else {
            $events = $schedule->getDueEvents();
        }

        $names = $io->getArgument('names');

        foreach ($events as $event) {
            if ($names !== [] && !in_array($event->getName(), $names, true)) {
                continue;
            }

            $event->execute();
        }

        return 0;
    }

    protected function loadScheduleTasks(Schedule $schedule, string $route): void
    {
        $app = $this->app;

        include $route;
    }
}
