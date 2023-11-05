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
    description: 'Generate Windwalker form field.'
)]
class FieldSubCommand extends AbstractGeneratorSubCommand
{
    protected bool $requireDest = false;

    protected string $defaultNamespace = 'Field';

    protected string $defaultDir = 'Field';

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
            $io->errorStyle()->error('No field name');

            return 255;
        }

        $this->codeGenerator->from($this->getViewPath('field/**/*.tpl'))
            ->replaceTo(
                $this->getDestPath($io),
                [
                    'className' => Str::ensureRight($name, 'Field'),
                    'name' => Str::removeRight($name, 'Field'),
                    'ns' => $this->getNamespace($io),
                ],
                $force
            );

        return 0;
    }
}
