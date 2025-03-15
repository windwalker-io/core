<?php

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

        $exists = $this->cronExists($cronContent, $matches);

        if (!$exists) {
            $io->writeln('Schedule not exists.');

            return 0;
        }

        $cronContent = $this->removeExpression($cronContent);

        $this->replaceCrontab($cronContent);

        $io->writeln("[REMOVE] >> {$matches[0]}");
        $io->writeln("Uninstall schedule successfully.");

        return 0;
    }
}
