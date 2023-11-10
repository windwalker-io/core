<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule\Command;

use Lorisleiva\CronTranslator\CronTranslator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Schedule\Schedule;
use Windwalker\Core\Schedule\ScheduleService;
use Windwalker\Utilities\Arr;

/**
 * The ScheduleShowCommand class.
 */
#[CommandWrapper(description: 'Run CRON schedule')]
class ScheduleShowCommand implements CommandInterface
{
    public function __construct(protected ScheduleService $scheduleService)
    {
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function execute(IOInterface $io): int
    {
        $schedule = $this->scheduleService->getSchedule();

        $this->showExpressions($schedule, $io);

        return 0;
    }

    protected function showExpressions(Schedule $schedule, IOInterface $io): void
    {
        $tags = $io->getOption('tags') ?? '';
        $tags = Arr::explodeAndClear(',', $tags);
        $names = $io->getArgument('names');

        $table = new Table($io);
        $table->setHeaderTitle('Schedule Events');
        $table->setHeaders(['Event', 'Expression', 'Describe', 'Due', 'Next Due', 'Tags']);

        $events = $schedule->getEvents($tags);

        $count = 0;
        $tz = $io->getOption('tz');
        $time = $io->getOption('time') ?: 'now';
        $canDescribe = class_exists(CronTranslator::class);

        foreach ($events as $event) {
            if ($names !== [] && !in_array($event->getName(), $names, true)) {
                continue;
            }

            $count++;
            $tags = $event->getTags();
            sort($tags);

            $expr = $event->getExpression();
            $nextDue0 = $expr->getNextRunDate($time, 0, false, $tz);
            $nextDue1 = $expr->getNextRunDate($time, 1, false, $tz);
            $nextDue2 = $expr->getNextRunDate($time, 2, false, $tz);

            if ($count !== 1) {
                $table->addRow(new TableSeparator());
            }

            $table->addRow(
                [
                    '<fg=cyan>' . $event->getName() . '</>',
                    (string) $event,
                    $canDescribe ? CronTranslator::translate($expr->getExpression()) : '-',
                    $expr->isDue($time, $tz) ? '<info>v</info>' : '',
                    $nextDue0->format('Y-m-d H:i:s')
                    . "\n" . $nextDue1->format('Y-m-d H:i:s')
                    . "\n" . $nextDue2->format('Y-m-d H:i:s'),
                    '<fg=gray>' . implode(' ', $tags) . '</>',
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

        if (!$canDescribe) {
            $io->style()->warning("Consider install `lorisleiva/cron-translator` to describe CRON expression.");
        }
    }
}
