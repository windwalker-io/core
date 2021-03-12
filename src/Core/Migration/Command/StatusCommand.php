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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Core\Console\CommandInterface;
use Windwalker\Core\Console\CommandWrapper;
use Windwalker\Core\Console\IOInterface;
use Windwalker\Core\Migration\MigrationService;
use Windwalker\DI\Attributes\Autowire;
use Windwalker\DI\Attributes\Decorator;

/**
 * The StatusCommand class.
 */
#[CommandWrapper(description: 'Show migration status.')]
class StatusCommand extends AbstractMigrationCommand
{
    /**
     * StatusCommand constructor.
     *
     * @param  MigrationService  $migrationService
     */
    public function __construct(#[Autowire] protected MigrationService $migrationService)
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
    public function execute(IOInterface $io)
    {
        $this->preprocessMigrations($io);

        $migrations = $this->migrationService->getMigrations($this->getMigrationFolder($io));

        if ($migrations === []) {
            $io->writeln('No migrations found.');
            return 0;
        }

        ksort($migrations);
        $versions = $this->migrationService->getVersions();

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
                    '<comment>' . $migration->name . '</comment>'
                ]
            );
        }

        $io->newLine();
        $table->render();
        $io->newLine();

        return 0;
    }
}
