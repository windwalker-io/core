<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Core\Migration\MigrationService;

/**
 * The StatusCommand class.
 */
#[CommandWrapper(description: 'Show migration status.')]
class StatusCommand extends AbstractMigrationCommand
{
    /**
     * StatusCommand constructor.
     */
    public function __construct()
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
        parent::configure($command);

        $command->addOption(
            'no-create-db',
            null,
            InputOption::VALUE_REQUIRED,
            'Do not auto create database or schema.'
        );
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  mixed
     */
    public function execute(IOInterface $io): int
    {
        $this->preprocess(
            $io,
            static::TOGGLE_CONNECTION
            | static::CREATE_DATABASE
        );

        /** @var ConsoleApplication $app */
        $app = $this->app;

        $migrationService = $this->app->make(MigrationService::class);
        $migrations = $migrationService->getMigrations($this->getMigrationFolder($io));

        if ($migrations === []) {
            $io->writeln('No migrations found.');

            return 0;
        }

        ksort($migrations);
        $versions = $migrationService->getVersions();

        $table = new Table($io);
        $table->setHeaders(['Status', 'Version', 'Migration Name']);
        $table->setHeaderTitle('Migration Status');

        foreach ($migrations as $migration) {
            $status = in_array($migration->version, $versions, true)
                ? '<info>up</info>'
                : '<error>down</error>';

            $table->addRow(
                [
                    $status,
                    $migration->version,
                    '<comment>' . $migration->name . '</comment>',
                ]
            );
        }

        $io->newLine();
        $table->render();
        $io->newLine();

        return 0;
    }
}
