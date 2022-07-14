<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
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
 * The MigrateBackCommand class.
 */
#[CommandWrapper(
    description: 'Migrate back some steps.'
)]
class MigrateBackCommand extends AbstractMigrationCommand
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
            'step',
            InputArgument::OPTIONAL,
            'The target version, leave empty to migrate to last.',
            '1'
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
            | static::AUTO_BACKUP
        );

        // Change dir or package

        $style = $io->style();
        $style->title('Migration start...');

        $migrationService = $this->app->make(MigrationService::class);
        $migrationService->addEventDealer($this->app);

        $step = (int) $io->getArgument('step');
        $versions = $migrationService->getVersions();
        $currentVersion = $migrationService->getCurrentVersion();

        $index = array_search($currentVersion, $versions, true) ?: array_key_last($versions);

        $targetIndex = max(0, $index - $step);

        $version = $versions[$targetIndex] ?? '0';

        $count = $migrationService->migrate(
            $this->getMigrationFolder($io),
            (string) $version,
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
