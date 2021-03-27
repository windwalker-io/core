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
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Migration\MigrationService;

/**
 * The CreateCommand class.
 */
#[CommandWrapper(description: 'Create a migration version.')]
class CreateCommand extends AbstractMigrationCommand
{
    /**
     * CreateCommand constructor.
     *
     * @param  MigrationService  $migrationService
     */
    public function __construct(protected MigrationService $migrationService)
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

        $command->addArgument(
            'name',
            InputArgument::REQUIRED,
            'Migration name',
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
        $name = $io->getArgument('name');

        $file = $this->migrationService->copyMigrationFile(
            $this->getMigrationFolder($io),
            $name,
            __DIR__ . '/../../../../resources/templates/migration/migration.tpl'
        );

        $io->newLine();
        $io->writeln('Migration version: <info>' . $file->getBasename('.php') . '</info> created.');
        $io->writeln('File path: <info>' . $file->getPathname() . '</info>');

        return 0;
    }
}
