<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Seed\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Migration\Command\AbstractMigrationCommand;
use Windwalker\Core\Seed\SeedService;
use Windwalker\Filesystem\FileObject;

/**
 * The SeedImportCommand class.
 */
#[CommandWrapper(description: 'Import seeders')]
class SeedImportCommand extends AbstractSeedCommand
{
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
            'no-backup',
            null,
            InputOption::VALUE_OPTIONAL,
            'Do not backup database.',
            false
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
        if (!$this->confirm($io)) {
            return 255;
        }

        $this->preprocess(
            $io,
            static::NO_TIME_LIMIT
            | static::TOGGLE_CONNECTION
            | static::AUTO_BACKUP
        );

        $file = new FileObject($this->getSeederFile($io));

        /** @var SeedService $seedService */
        $seedService = $this->app->make(SeedService::class);
        $seedService->addEventDealer($this->app);

        $count = $seedService->import($file);

        $io->newLine();

        return 0;
    }
}
