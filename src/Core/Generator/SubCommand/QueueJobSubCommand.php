<?php

declare(strict_types=1);

namespace Windwalker\Core\Generator\SubCommand;

use Symfony\Component\Console\Command\Command;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Utilities\Str;

/**
 * The GenEnumSubCommand class.
 */
#[CommandWrapper(
    description: 'Generate Windwalker queue job.'
)]
class QueueJobSubCommand extends AbstractGeneratorSubCommand
{
    protected string $defaultNamespace = 'Queue';

    protected string $defaultDir = 'Queue';

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
            $io->errorStyle()->error('No job name');

            return 255;
        }

        $this->codeGenerator->from($this->getViewPath('job/*.tpl'))
            ->replaceTo(
                $this->getDestPath($io),
                [
                    'className' => Str::ensureEnd($name, 'Job'),
                    'name' => Str::removeEnd($name, 'Job'),
                    'ns' => $this->getNamespace($io),
                ],
                $force
            );

        return 0;
    }
}
