<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

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
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Core\Schedule\Schedule;
use Windwalker\Core\Schedule\ScheduleService;
use Windwalker\Environment\Environment;
use Windwalker\Utilities\Arr;

use Windwalker\Utilities\Str;

use function Windwalker\fs;

/**
 * The ScheduleShowCommand class.
 */
#[CommandWrapper(description: 'Install CRON schedule')]
class ScheduleInstallCommand implements CommandInterface
{
    use CommandScheduleTrait;

    public function __construct(
        protected ScheduleService $scheduleService
    ) {
    }

    /**
     * @inheritDoc
     */
    public function configure(Command $command): void
    {
        $command->addOption(
            'tz',
            't',
            InputOption::VALUE_REQUIRED,
            'Timezone.'
        );

        $command->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
        );
    }

    /**
     * @inheritDoc
     */
    public function execute(IOInterface $io): int
    {
        if (Environment::isWindows()) {
            throw new \RuntimeException('Schedule install not supports Windows platform.');
        }

        $cronContent = trim($this->app->runProcess('crontab -l')->getOutput());

        $exists = $this->cronExists($cronContent);

        $force = $io->getOption('force');

        if ($exists && !$force) {
            throw new \RuntimeException('Schedule already exists.');
        }

        if ($force) {
            $cronContent = $this->removeExpression($cronContent);

            $this->replaceCrontab($cronContent);
        }

        $expr = $this->getScheduleExpression($io->getOption('tz') ?? '');
        $this->appendCrontab($expr);

        $io->writeln("Install schedule to crontab");

        return 0;
    }
}
