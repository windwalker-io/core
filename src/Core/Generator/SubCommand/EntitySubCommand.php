<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Generator\SubCommand;

use Symfony\Component\Console\Command\Command;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Utilities\Str;

/**
 * The GenViewSubCommand class.
 */
#[CommandWrapper(
    description: 'Generate Windwalker entity.'
)]
class EntitySubCommand extends AbstractGeneratorSubCommand
{
    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  int Return 0 is success, 1-255 is failure.
     * @throws \ReflectionException
     */
    public function execute(IOInterface $io): int
    {
        [, $name] = $this->getNameParts($io);
        $force = $io->getOption('force');

        if (!$name) {
            $io->errorStyle()->error('No entity name');

            return 255;
        }

        $this->codeGenerator->from($this->getViewPath('model/entity/*.tpl'))
            ->replaceTo(
                $this->app->path('src/Entity'),
                [
                    'name' => $name,
                    'ns' => $this->getNamesapce($io),
                ],
                $force
            );

        return 0;
    }
}
