<?php

declare(strict_types=1);

namespace Windwalker\Core\Queue\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Core\Migration\MigrationService;

/**
 * The QueueTableCommand class.
 */
#[CommandWrapper(
    description: 'Create jobs migration file.'
)]
class QueueTableCommand implements CommandInterface
{
    public function __construct(protected ConsoleApplication $app)
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
        $command->addArgument(
            'name',
            InputArgument::OPTIONAL,
            'Migration name.',
            'QueueJobInit'
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
        $migrationService = $this->app->service(MigrationService::class);

        $migrationService->copyMigrationFile(
            $this->app->path('@migrations'),
            $io->getArgument('name'),
            __DIR__ . '/../../../../resources/templates/queue/table/*'
        );

        return 0;
    }
}
