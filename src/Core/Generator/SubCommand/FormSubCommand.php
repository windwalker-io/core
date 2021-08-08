<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Generator\SubCommand;

use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Utilities\Str;

/**
 * The GenViewSubCommand class.
 */
#[CommandWrapper(
    description: 'Generate Windwalker form.'
)]
class FormSubCommand extends AbstractGeneratorSubCommand
{
    /**
     * Executes the current command.
     *
     * @param  IOInterface  $io
     *
     * @return  int Return 0 is success, 1-255 is failure.
     */
    public function execute(IOInterface $io): int
    {
        [, $name] = $this->getNameParts($io);
        $force = $io->getOption('force');

        if (!$name) {
            $io->errorStyle()->error('No form name');

            return 255;
        }

        $this->codeGenerator->from($this->getViewPath('form/**/*.tpl'))
            ->replaceTo(
                $this->getDestPath($io),
                [
                    'className' => Str::ensureRight($name, 'Form'),
                    'name' => Str::removeRight($name, 'Form'),
                    'ns' => $this->getNamesapce($io),
                ],
                $force
            );

        return 0;
    }
}
