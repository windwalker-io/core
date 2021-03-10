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
use Windwalker\Core\Console\CommandWrapperInterface;
use Windwalker\Core\Console\CoreCommand;
use Windwalker\Core\Console\IOInterface;
use Windwalker\DI\Attributes\Decorator;

/**
 * The MigrationToCommand class.
 */
#[Decorator(MigrationWrapper::class)]
#[CoreCommand(
    name: 'migration:to',
    description: 'Migrate to specific version.'
)]
class MigrationToCommand implements CommandWrapperInterface
{
    /**
     * @inheritDoc
     */
    public function configure(Command $command): void
    {
        $command->addOption(
            'seed',
            's',
            InputOption::VALUE_OPTIONAL,
            'Also import seeds.'
        );
        $command->addOption(
            'no-backup',
            null,
            InputOption::VALUE_OPTIONAL,
            'Do not backup database.'
        );
        $command->addOption(
            'no-create-db',
            null,
            InputOption::VALUE_OPTIONAL,
            'Do not auto create database or schema.'
        );
    }

    /**
     * @inheritDoc
     */
    public function execute(IOInterface $io): mixed
    {
        $a = $b;
        show('Hello', $this->getMigrationFolder());
    }
}
