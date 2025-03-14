<?php

declare(strict_types=1);

namespace Windwalker\Core\Migration\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Migration\MigrationSquashService;

#[CommandWrapper(
    description: 'Squash current database schema as squashed.'
)]
class MigSquashCommand extends AbstractMigrationCommand
{
    public function __construct()
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
            'one',
            'o',
            InputOption::VALUE_NONE,
            'Squash to only one file.',
        );

        $command->addOption(
            'group',
            'g',
            InputOption::VALUE_NEGATABLE,
            'Group tables with first part of name.',
            true,
        );

        $command->addOption(
            'no-backup',
            null,
            InputOption::VALUE_NONE,
            'Do not backup database.',
        );
    }

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  int Return 0 is success, 1-255 is failure.
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

        $db = $this->getDatabaseConnection($io);

        $squashService = $this->app->make(MigrationSquashService::class);
        $squashService->addEventDealer($this->app);

        $group = $io->getOption('group');
        $one = $io->getOption('one');

        $squashService->squash(
            db: $db,
            path: $this->getMigrationFolder($io),
            group: $group,
            one: $one
        );

        return 0;
    }
}
