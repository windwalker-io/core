<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Console\CommandInterface;
use Windwalker\Core\Console\CommandWrapper;
use Windwalker\Core\Console\IOInterface;
use Windwalker\Core\Database\DatabaseExportService;
use Windwalker\Core\DateTime\Chronos;
use Windwalker\Core\Manager\DatabaseManager;
use Windwalker\Filesystem\FileObject;

/**
 * The DbExportCommand class.
 */
#[CommandWrapper('Export database to file.')]
class DbExportCommand implements CommandInterface
{
    /**
     * DbExportCommand constructor.
     *
     * @param  DatabaseManager        $databaseManager
     * @param  ApplicationInterface   $app
     * @param  DatabaseExportService  $databaseExportService
     */
    public function __construct(
        protected DatabaseManager $databaseManager,
        protected ApplicationInterface $app,
        protected DatabaseExportService $databaseExportService
    ) {
    }

    /**
     * @inheritDoc
     */
    public function configure(Command $command): void
    {
        $default = $this->databaseManager->getDefaultName();

        $command->addArgument(
            'dest',
            InputArgument::OPTIONAL,
            'The export dest file.',
        );

        $command->addOption(
            'connection',
            'c',
            InputOption::VALUE_REQUIRED,
            'Connection to export, default is: ' . $default
        );
    }

    /**
     * @inheritDoc
     */
    public function execute(IOInterface $io): int
    {
        $now  = new \DateTimeImmutable('now');
        $conn = $io->getOption('connection');

        $dest = $io->getArgument('dest') ?: sprintf(
            $this->app->path('@temp/sql-export/sql-%s-%s.sql'),
            $conn ?? $this->databaseManager->getDefaultName(),
            $now->format('Y-m-d-H-i-s')
        );

        $file = FileObject::wrap($dest);
        $file->getParent()->mkdir();

        $io->writeln('Start exporting SQL...');

        $dest = $this->databaseExportService->exportTo(
            $file,
            $this->databaseManager->get($conn),
            $io
        );

        $io->writeln("Exported to <info>{$dest->getPathname()}</info>");

        return 0;
    }
}
