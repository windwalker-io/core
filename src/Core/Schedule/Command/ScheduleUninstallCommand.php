<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Schedule\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Schedule\ScheduleService;
use Windwalker\Environment\Environment;

/**
 * The ScheduleShowCommand class.
 */
#[CommandWrapper(description: 'Uninstall CRON schedule')]
class ScheduleUninstallCommand implements CommandInterface
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
        //
    }

    /**
     * @inheritDoc
     */
    public function execute(IOInterface $io): int
    {
        if (Environment::isWindows()) {
            throw new \RuntimeException('Schedule install not supports Windows platform.');
        }

        $cronContent = $this->app->runProcess('crontab -l')->getOutput();

        $expr = $this->getScheduleExpression();

        $exists = $this->cronExists($cronContent, $expr);

        if (!$exists) {
            $io->writeln('Schedule not exists.');

            return 0;
        }

        $cronContent = $this->removeExpression($cronContent, $expr);

        $this->replaceCrontab($cronContent);

        $io->writeln("Uninstall schedule successfully.");

        return 0;
    }
}
