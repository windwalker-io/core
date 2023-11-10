<?php

declare(strict_types=1);

namespace Windwalker\Core\Generator\SubCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;

/**
 * The GenEnumSubCommand class.
 */
#[CommandWrapper(
    description: 'Generate Windwalker enum.'
)]
class EnumSubCommand extends AbstractGeneratorSubCommand
{
    protected string $defaultNamespace = 'Enum';

    protected string $defaultDir = 'Enum';

    protected bool $requireDest = false;

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

        $command->addOption(
            'options',
            'o',
            InputOption::VALUE_OPTIONAL
        );
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
        [, $name] = $this->getNameParts($io);
        $force = $io->getOption('force');

        if (!$name) {
            $io->errorStyle()->error('No enum name');

            return 255;
        }

        $this->codeGenerator->from($this->getViewPath('enum/*.tpl'))
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
