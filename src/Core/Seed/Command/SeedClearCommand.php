<?php

declare(strict_types=1);

namespace Windwalker\Core\Seed\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Seed\SeedService;
use Windwalker\Filesystem\FileObject;

/**
 * The SeedClearCommand class.
 */
#[CommandWrapper(description: 'Clear seeders')]
class SeedClearCommand extends AbstractSeedCommand
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

        $count = $seedService->clear($file);

        $io->newLine();

        return 0;
    }
}
