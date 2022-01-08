<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Database\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Database\DatabaseExportService;
use Windwalker\Core\Manager\DatabaseManager;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Filesystem\FileObject;
use Windwalker\Filesystem\Path;
use Windwalker\Utilities\StrNormalize;

/**
 * The DbExportCommand class.
 */
#[CommandWrapper(description: 'Export database to file.')]
class DbExportCommand implements CommandInterface
{
    /**
     * DbExportCommand constructor.
     *
     * @param  ApplicationInterface   $app
     */
    public function __construct(
        protected ApplicationInterface $app,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function configure(Command $command): void
    {
        try {
            $databaseManager = $this->app->service(DatabaseManager::class);
            $default = $databaseManager->getDefaultName();
        } catch (\Throwable $e) {
            $default = 'local';
        }

        $command->addArgument(
            'dest',
            InputArgument::OPTIONAL,
            'The export dest file.',
        );

        $command->addOption(
            'connection',
            'c',
            InputOption::VALUE_REQUIRED,
            'Connection to export, default is: ' . ($default ?? 'local')
        );
    }

    /**
     * @inheritDoc
     */
    public function execute(IOInterface $io): int
    {
        if (!class_exists(DatabaseAdapter::class)) {
            throw new \DomainException('Please install windwalker/database first.');
        }

        $databaseManager = $this->app->service(DatabaseManager::class);

        $now  = new \DateTimeImmutable('now');
        $conn = $io->getOption('connection');

        if ($conn) {
            $this->app->getContainer()->getParameters()->setDeep('database.default', $conn);
        }

        $appName = $this->app->config('app.name') ?? 'windwalker';

        $dest = $io->getArgument('dest') ?: sprintf(
            $this->app->path('@temp/sql-export/%s-sql-%s-%s.sql'),
            StrNormalize::toKebabCase(Path::makeUtf8Safe($appName)),
            $conn ?? $databaseManager->getDefaultName(),
            $now->format('Y-m-d-H-i-s')
        );

        $file = FileObject::wrap($dest);
        $file->getParent()->mkdir();

        $io->writeln('Start exporting SQL...');

        $databaseExportService = $this->app->make(DatabaseExportService::class);

        $dest = $databaseExportService->exportTo($file, $io);

        $io->writeln("Exported to <info>{$dest->getPathname()}</info>");

        return 0;
    }
}
