<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 .
 * @license    MIT
 */

namespace Windwalker\Core\Schedule\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Schedule\Schedule;
use Windwalker\Core\Schedule\ScheduleEvent;
use Windwalker\Utilities\Arr;

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
            'show',
            's',
            InputOption::VALUE_OPTIONAL,
            'Show all expressions.',
            false
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
        $routes = (array) $this->app->config('schedules');

        $schedule = $this->app->make(Schedule::class);

        foreach ($routes as $route) {
            $this->loadScheduleTasks($schedule, $route);
        }

        if ($io->getOption('show') !== false) {
            $this->showExpressions($schedule, $io);
            return 0;
        }

        foreach ($this->getAvailableEvents($schedule, $io) as $event) {
            $this->runEvent($event);
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

    protected function getAvailableEvents(Schedule $schedule, IOInterface $io): \Generator
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

    protected function showExpressions(Schedule $schedule, IOInterface $io): void
    {
        $tags = $io->getOption('tags') ?? '';
        $tags = Arr::explodeAndClear(',', $tags);
        $names = $io->getArgument('names');

        $table = new Table($io);
        $table->setHeaderTitle('Schedule Events');
        $table->setHeaders(['Event', 'Expression', 'Due', 'Next Due', 'Tags']);

        $events = $schedule->getEvents($tags);

        $count = 0;
        $tz = $io->getOption('tz');
        $time = $io->getOption('time') ?: 'now';

        foreach ($events as $event) {
            if ($names !== [] && !in_array($event->getName(), $names, true)) {
                continue;
            }

            $count++;
            $tags = $event->getTags();
            sort($tags);

            $expr    = $event->getExpression();
            $nextDue = $expr->getNextRunDate($time, 0, false, $tz);

            $table->addRow(
                [
                    '<fg=cyan>' . $event->getName() . '</>',
                    (string) $event,
                    $expr->isDue($time, $tz) ? '<info>v</info>' : '',
                    $nextDue->format('Y-m-d H:i:s'),
                    '<fg=gray>' . implode(' ', $tags) . '</>'
                ]
            );
        }

        if ($count === 0) {
            $io->writeln('No events.');
            $io->newLine();
            return;
        }

        $io->newLine();
        $table->render();
        $io->newLine();
    }

    protected function loadScheduleTasks(Schedule $schedule, string $route): void
    {
        $app = $this->app;

        include $route;
    }
}
