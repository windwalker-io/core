<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Generator\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\InteractInterface;
use Windwalker\Console\IOInterface;

/**
 * The GenControllerSubCommand class.
 */
#[CommandWrapper(
    description: 'Generate Windwalker controller.'
)]
class GenControllerSubCommand implements CommandInterface, InteractInterface
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
        $command->addArgument(
            'name',
            InputArgument::OPTIONAL,
        );

        $command->addArgument(
            'dest',
            InputArgument::OPTIONAL,
        );
    }

    /**
     * Interaction with user.
     *
     * @param  IOInterface  $io
     *
     * @return  void
     */
    public function interact(IOInterface $io): void
    {
        if (!$io->getArgument('name')) {
            $io->setArgument('name', $io->ask('Controller name: '));
        }

        if (!$io->getArgument('dest')) {
            $io->setArgument('dest', $io->ask('Dest path: '));
        }
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
        $name = $io->getArgument('name');
        $dest = $io->getArgument('dest');




        return 0;
    }
}
