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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Migration\MigrationService;

/**
 * The MigrationToCommand class.
 */
#[CommandWrapper(
    description: 'Migrate to specific version or latest.'
)]
class MigrateCommand extends AbstractMigrationCommand
{
    /**
     * MigrationToCommand constructor.
     *
     * @param  MigrationService  $migrationService
     */
    public function __construct(protected MigrationService $migrationService)
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function configure(Command $command): void
    {
        parent::configure($command);

        $command->addArgument(
            'version',
            InputArgument::OPTIONAL,
            'The target version, leave empty to migrate to last.',
            null
        );
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
     * @inheritDoc
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

        // Change dir or package

        $style = $io->style();
        $style->title('Migration start...');

        $this->migrationService->addEventDealer($this->app);

        $count = $this->migrationService->migrate(
            $this->getMigrationFolder($io),
            $io->getArgument('version'),
            $this->getLogFile($io)
        );

        if ($count) {
            $io->newLine();
            $io->writeln('Completed.');
        }

        $io->newLine();

        return 0;
    }
}
