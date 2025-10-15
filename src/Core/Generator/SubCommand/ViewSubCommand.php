<?php

declare(strict_types=1);

namespace Windwalker\Core\Generator\SubCommand;

use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Utilities\Str;

/**
 * The GenViewSubCommand class.
 */
#[CommandWrapper(
    description: 'Generate Windwalker view.'
)]
class ViewSubCommand extends AbstractGeneratorSubCommand
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
        [, $name, $stage] = $this->getNameParts($io, 'View');
        $force = $io->getOption('force');

        if (!$name) {
            $io->errorStyle()->error('No view name');

            return 255;
        }

        $this->codeGenerator->from($this->getViewPath('view/**/*.tpl'))
            ->replaceTo(
                $this->getDestPath($io, 'View'),
                [
                    'className' => Str::ensureRight($name, 'View'),
                    'name' => Str::removeRight($name, 'View'),
                    'ns' => $this->getNamespace($io, 'View'),
                    'stage' => $stage,
                ],
                $force
            );

        return 0;
    }
}
