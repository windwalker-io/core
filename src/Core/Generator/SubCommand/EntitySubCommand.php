<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Generator\SubCommand;

use ReflectionException;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;

/**
 * The GenViewSubCommand class.
 */
#[CommandWrapper(
    description: 'Generate Windwalker entity.'
)]
class EntitySubCommand extends AbstractGeneratorSubCommand
{
    protected string $defaultNamespace = 'Entity';

    protected string $defaultDir = 'Entity';

    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  int Return 0 is success, 1-255 is failure.
     * @throws ReflectionException
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
                $this->getDestPath($io),
                [
                    'name' => $name,
                    'ns' => $this->getNamespace($io),
                ],
                $force
            );

        return 0;
    }
}
