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
use Windwalker\Console\CommandInterface;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Database\DatabaseExportService;
use Windwalker\Core\Manager\DatabaseManager;
use Windwalker\DI\Attributes\Inject;
use Windwalker\Environment\PlatformHelper;

/**
 * The AbstractMigrationCommand class.
 */
abstract class AbstractMigrationCommand implements CommandInterface
{
    #[Inject]
    protected ?ApplicationInterface $app = null;

    #[Inject]
    protected ?DatabaseExportService $databaseExportService = null;

    #[Inject]
    protected ?DatabaseManager $databaseManager = null;

    /**
     * @inheritDoc
     */
    public function configure(Command $command): void
    {
        $command->addOption(
            'dir',
            'd',
            InputOption::VALUE_REQUIRED,
            'The migration files directory.'
        );

        $command->addOption(
            'package',
            'p',
            InputOption::VALUE_REQUIRED,
            'The target package migrations.'
        );

        $command->addOption(
            'connection',
            'c',
            InputOption::VALUE_REQUIRED,
            'The database connection name.'
        );
    }

    /**
     * Create database if not exists before migration command start.
     *
     * @param  IOInterface  $io
     *
     * @return  void
     */
    protected function createDatabase(IOInterface $io): void
    {
        $conn = $io->getOption('connection');
        $factory = $this->databaseManager->getDatabaseFactory();

        $db = $this->databaseManager->get($conn);
        $db->disconnect();

        $options = $db->getOptions();

        $dbname = $options['dbname'];
        $options['dbname'] = null;

        $dbPreset = $factory->create(
            $options['driver'],
            $options
        );

        $schema = $dbPreset->getSchema($dbname);

        if (!$schema->exists()) {
            $schema->create();
            $io->writeln('');
            $io->writeln("Database (Schema) <info>{$dbname}</info> auto created.");
        }

        $dbPreset->disconnect();
    }

    /**
     * The system default migration folder.
     *
     * @return  string
     */
    public function getDefaultMigrationFolder(): string
    {
        return $this->app->config('@migrations');
    }

    /**
     * Get migration folder from options or return default.
     *
     * @param  IOInterface  $io
     *
     * @return  string
     */
    public function getMigrationFolder(IOInterface $io): string
    {
        $dir = $io->getOption('dir');

        // todo: package dir

        return $dir ?: $this->getDefaultMigrationFolder();
    }

    /**
     * Get log file from options or return default.
     *
     * @param  IOInterface  $io
     *
     * @return  string|null
     */
    protected function getLogFile(IOInterface $io): ?string
    {
        $log = $io->getOption('log');

        if ($log === false) {
            return null;
        }

        if ($log === null) {
            $log = $this->app->config('@temp') . '/db/last-migration-logs.sql';
        }

        return $log;
    }

    /**
     * Do auto backup if allowed.
     *
     * @param  IOInterface  $io
     *
     * @return  void
     *
     * @throws \Exception
     */
    protected function backup(IOInterface $io): void
    {
        if ($io->getOption('no-backup') === false) {
            $io->writeln('');
            $io->writeln('<fg=gray>Backing up SQL...</>');

            $dest = $this->databaseExportService->export($io->getOption('connection'), $io);

            $io->writeln('SQL backup to: <info>' . $dest->getRealPath() . '</info>');
            $io->style()->newLine();
        }
    }

    /**
     * Check APP_ENV is dev or stop.
     *
     * @param  IOInterface  $io
     *
     * @return  bool
     */
    protected function checkEnv(IOInterface $io): bool
    {
        if (env('APP_ENV') !== 'dev') {
            $io->writeln('<error>STOP!</error> please run: <info>' . $this->getEnvCmd() . '</info>');
            return false;
        }

        return true;
    }

    /**
     * Get the ENV suggestion depend on platform.
     *
     * @param string $env
     * @param string $value
     *
     * @return  string
     *
     * @since  3.5.3
     */
    public function getEnvCmd(string $env = 'APP_ENV', string $value = 'dev'): string
    {
        $prefix = PlatformHelper::isWindows()
            ? 'set'
            : 'export';

        return sprintf('%s %s=%s', $prefix, $env, $value);
    }
}
