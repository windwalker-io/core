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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Migration\MigrationService;
use Windwalker\Core\Seed\SeedService;
use Windwalker\Filesystem\FileObject;

/**
 * The MigrationToCommand class.
 */
#[CommandWrapper(
    description: 'Migrate to specific version or latest.'
)]
class MigrateGoCommand extends AbstractMigrationCommand
{
    /**
     * MigrationToCommand constructor.
     */
    public function __construct()
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
        if (!$this->confirm($io)) {
            return 255;
        }

        $this->preprocess(
            $io,
            static::NO_TIME_LIMIT
            | static::TOGGLE_CONNECTION
            | static::CREATE_DATABASE
            | static::AUTO_BACKUP
        );

        // Change dir or package

        $style = $io->style();
        $style->title('Migration start...');

        $migrationService = $this->app->make(MigrationService::class);
        $migrationService->addEventDealer($this->app);

        $count = $migrationService->migrate(
            $this->getMigrationFolder($io),
            $io->getArgument('version'),
            $this->getLogFile($io)
        );

        if ($count) {
            $io->newLine();
            $io->writeln('Completed.');
        }

        $seed = $io->getOption('seed');

        if ($seed !== false) {
            $style->newLine(2);
            $style->title('Seeding...');

            /** @var SeedService $seedService */
            $seedService = $this->app->make(SeedService::class);
            $seedService->addEventDealer($this->app);

            if (!$seed) {
                $seed = $this->app->path('@seeders/');
            }

            if (is_dir($seed)) {
                $seed .= '/main.php';
            }

            $count = $seedService->import(new FileObject($seed));
        }

        $io->newLine();

        return 0;
    }
}
