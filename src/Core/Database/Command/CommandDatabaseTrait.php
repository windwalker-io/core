<?php

declare(strict_types=1);

namespace Windwalker\Core\Database\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Core\Manager\DatabaseManager;
use Windwalker\DI\Attributes\Inject;

/**
 * Trait CommandDatabaseTrait
 */
trait CommandDatabaseTrait
{
    #[Inject]
    protected ?DatabaseManager $databaseManager = null;

    public function configureDatabaseOptions(Command $command): void
    {
        $command->addOption(
            'connection',
            'c',
            InputOption::VALUE_REQUIRED,
            'The database connection name.'
        );
    }
}
