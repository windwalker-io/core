<?php

declare(strict_types=1);

namespace Windwalker\Core\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Throwable;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\CompletionContext;
use Windwalker\Console\CompletionHandlerInterface;
use Windwalker\Console\Input\InputArgument;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Core\Migration\Migration;
use Windwalker\Core\Migration\MigrationService;

/**
 * The StatusCommand class.
 */
#[CommandWrapper(description: 'Show migration status.')]
class StatusCommand extends AbstractMigrationCommand implements CompletionHandlerInterface
{
    /**
     * StatusCommand constructor.
     */
    public function __construct() {}

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
            InputOption::VALUE_NONE,
            'Do not auto create database or schema.'
        );

        $command->addArgument(
            'task',
            InputArgument::OPTIONAL,
            'The subtask: `current`|`last`|`is-done`.'
        );

        $command->addOption(
            'show-name',
            'N',
            InputOption::VALUE_NONE,
            'Display version as name.'
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
        $databaseAvailable = true;

        $this->preprocess($io, static::TOGGLE_CONNECTION);

        try {
            $this->preprocess($io, static::CREATE_DATABASE);
        } catch (Throwable $e) {
            $databaseAvailable = false;
            $io->errorStyle()->warning($e->getMessage());
        }

        $task = $io->getArgument('task');
        $showName = $io->getOption('show-name');

        /** @var ConsoleApplication $app */
        $app = $this->app;

        $migrationService = $this->app->make(MigrationService::class);
        $migrations = $migrationService->getMigrations($this->getMigrationFolder($io));

        if ($task === 'current') {
            $versions = $this->getVersions($io, $migrationService, $databaseAvailable);
            $currentVersion = $versions === [] ? '0' : $versions[array_key_last($versions)];

            if ($currentVersion) {
                if ($showName) {
                    /** @var ?Migration $mig */
                    $mig = $migrations[$currentVersion] ?? null;

                    if ($mig) {
                        $currentVersion = $mig->name;
                    }
                }

                $io->writeln($currentVersion);
            }

            return 0;
        }

        if ($task === 'last') {
            $lastMig = $migrations[array_key_last($migrations)] ?? null;

            if ($lastMig) {
                if ($showName) {
                    $io->writeln($lastMig->version);
                } else {
                    $io->writeln($lastMig->name);
                }
            }

            return 0;
        }

        if ($task === 'is-done') {
            ksort($migrations);
            $versions = $this->getVersions($io, $migrationService, $databaseAvailable);

            foreach ($migrations as $migration) {
                if (!in_array($migration->version, $versions, true)) {
                    return 0;
                }
            }

            $io->writeln('done');

            return 0;
        }

        if ($migrations === []) {
            $io->writeln('No migrations found.');
        }

        ksort($migrations);
        $versions = $migrations === []
            ? []
            : $this->getVersions($io, $migrationService, $databaseAvailable);

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

    /**
     * @return  array<int, string>
     */
    private function getVersions(IOInterface $io, MigrationService $migrationService, bool $databaseAvailable): array
    {
        if (!$databaseAvailable) {
            return [];
        }

        try {
            return $migrationService->getVersions();
        } catch (Throwable $e) {
            $io->errorStyle()->warning($e->getMessage());

            return [];
        }
    }

    #[\Override]
    public function handleCompletions(CompletionContext $context): ?array
    {
        if ($context->isArgument()) {
            if ($context->name === 'task') {
                return [
                    'current',
                    'last',
                    'is-done',
                ];
            }
        }

        return null;
    }
}
