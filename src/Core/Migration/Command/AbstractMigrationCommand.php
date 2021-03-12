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
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Console\CommandInterface;
use Windwalker\Core\Console\IOInterface;
use Windwalker\Core\Database\DatabaseExportService;
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

    protected function preprocessMigrations(IOInterface $io): void
    {
        //
    }

    public function getDefaultMigrationFolder(): string
    {
        return $this->app->config('@migrations');
    }

    public function getMigrationFolder(IOInterface $io): string
    {
        $dir = $io->getOption('dir');

        // todo: package dir

        return $dir ?: $this->getDefaultMigrationFolder();
    }

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

    protected function backup(IOInterface $io): void
    {
        if (!$io->getOption('no-backup')) {
            $this->databaseExportService->export($io->getOption('connection'), $io);
            $io->style()->newLine();
        }
    }

    protected function checkEnv(IOInterface $io): bool
    {
        if (env('APP_ENV') !== 'dev') {
            $io->writeln('<error>STOP!</error> please run: <info>' . $this->getEnvCmd() . '</info>');
            return false;
        }

        return true;
    }

    /**
     * getEnvCmd
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
