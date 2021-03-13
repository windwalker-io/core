<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Migration\MigrationService;

/**
 * The ResetCommand class.
 */
#[CommandWrapper(description: 'Reset migration versions.')]
class ResetCommand extends AbstractMigrationCommand
{
    /**
     * ResetCommand constructor.
     *
     * @param  MigrationService  $migrationService
     */
    public function __construct(protected MigrationService $migrationService)
    {
        //
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
        parent::configure($command);

        $command->addOption(
            'seed',
            's',
            InputOption::VALUE_OPTIONAL,
            'Also import seeds.',
            false
        );
        $command->addOption(
            'log',
            'l',
            InputOption::VALUE_OPTIONAL,
            'Log queries, can provide a custom file.',
            false
        );
        $command->addOption(
            'no-backup',
            null,
            InputOption::VALUE_OPTIONAL,
            'Do not backup database.',
            false
        );
        $command->addOption(
            'no-create-db',
            null,
            InputOption::VALUE_OPTIONAL,
            'Do not auto create database or schema.',
            false
        );
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  mixed
     * @throws \Exception
     */
    public function execute(IOInterface $io): int
    {
        // Dev check
        if (!$this->checkEnv($io)) {
            return 255;
        }

        if ($io->getOption('no-create-db') === false) {
            $this->createDatabase($io);
        }

        set_time_limit(0);

        // Backup
        $this->backup($io);

        $this->migrationService->addEventDealer($this->app);

        $style = $io->style();
        $style->title('Rollback to 0 version...');

        $this->migrationService->migrate(
            $this->getMigrationFolder($io),
            '0'
        );

        $style->newLine(2);
        $style->title('Migrating to latest version...');

        $this->migrationService->migrate(
            $this->getMigrationFolder($io),
            null,
            $this->getLogFile($io)
        );

        $style->newLine();
        $io->writeln('Completed.');

        return 0;
    }
}
