<?php

declare(strict_types=1);

namespace Windwalker\Core\Database\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Manager\DatabaseManager;

/**
 * The DbDropAllCommand class.
 */
#[CommandWrapper(
    description: 'Drop all database'
)]
class DbDropAllCommand implements CommandInterface
{
    /**
     * DbExportCommand constructor.
     *
     * @param  DatabaseManager       $databaseManager
     * @param  ApplicationInterface  $app
     */
    public function __construct(
        protected DatabaseManager $databaseManager,
        protected ApplicationInterface $app,
    ) {
    }

    public function configure(Command $command): void
    {
        $default = $this->databaseManager->getDefaultName();

        $command->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force run, do not ask.'
        );

        $command->addOption(
            'connection',
            'c',
            InputOption::VALUE_REQUIRED,
            'Connection to export, default is: ' . $default
        );
    }

    public function execute(IOInterface $io): int
    {
        $qn = new ConfirmationQuestion(
            'Are you sure you want to drop all tables? [y/N]: ',
            false
        );

        if (!$io->getOption('force') && !$io->ask($qn)) {
            $io->writeln('Cancelled.');

            return 0;
        }

        $db = $this->databaseManager->get($io->getOption('connection'));

        $tables = $db->getSchemaManager()->getTables(false);

        foreach ($tables as $table) {
            $tm = $db->getTableManager($table->tableName);
            $tm->drop();

            $io->writeln('[DELETED] ' . $table->tableName);
        }

        $io->writeln('Completed.');

        return 0;
    }
}
