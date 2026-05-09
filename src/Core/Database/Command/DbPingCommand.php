<?php

declare(strict_types=1);

namespace Windwalker\Core\Database\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Factory\DatabaseServiceFactory;
use Windwalker\Database\DatabaseAdapter;

#[CommandWrapper(
    description: 'Ping the database to check it is live.',
)]
class DbPingCommand implements CommandInterface
{
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
            $databaseManager = $this->app->service(DatabaseServiceFactory::class);
            $default = $databaseManager->getDefaultName();
        } catch (Throwable $e) {
            $default = 'local';
        }

        $command->addOption(
            'connection',
            'c',
            InputOption::VALUE_REQUIRED,
            'Connection to export, default is: ' . ($default ?? 'local')
        );

        $command->addOption(
            'success-text',
            's',
            InputOption::VALUE_REQUIRED,
            'The return text when ping success, default is: `ok`',
            'ok'
        );
    }

    public function execute(IOInterface $io): int
    {
        $conn = $io->getOption('connection') ?? 'local';

        if (!class_exists(DatabaseAdapter::class)) {
            throw new \DomainException('Please install windwalker/database first.');
        }

        $dbFactory = $this->app->service(DatabaseServiceFactory::class);
        /** @var DatabaseAdapter $db */
        $db = $dbFactory->get($conn);

        if (!$db) {
            throw new \RuntimeException('Database connection not exists.');
        }

        if (!$db->ping()) {
            throw new \RuntimeException('Database is not live.');
        }

        $successText = $io->getOption('success-text');

        $io->writeln($successText);

        return 0;
    }
}
