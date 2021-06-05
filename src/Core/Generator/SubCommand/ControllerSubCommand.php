<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Generator\SubCommand;

use Symfony\Component\Console\Command\Command;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Utilities\Str;

/**
 * The GenControllerSubCommand class.
 */
#[CommandWrapper(
    description: 'Generate Windwalker controller.'
)]
class ControllerSubCommand extends AbstractGeneratorSubCommand
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
        $name  = $io->getArgument('name');
        $force = $io->getOption('force');

        if (!$name) {
            $io->errorStyle()->error('No controller name');

            return 255;
        }

        $this->codeGenerator->from($this->getViewPath('controller/*'))
            ->replaceTo(
                $this->getDestPath($io),
                [
                    'className' => Str::ensureRight($name, 'Controller'),
                    'name' => Str::removeRight($name, 'Controller'),
                    'ns' => $ns = $this->getNamesapce($io),
                ],
                $force
            );

        return 0;
    }
}
