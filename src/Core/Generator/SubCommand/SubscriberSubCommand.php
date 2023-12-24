<?php

declare(strict_types=1);

namespace Windwalker\Core\Generator\SubCommand;

use Symfony\Component\Console\Command\Command;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;

/**
 * The GenEnumSubCommand class.
 */
#[CommandWrapper(
    description: 'Generate Windwalker event subscriber.'
)]
class SubscriberSubCommand extends AbstractGeneratorSubCommand
{
    protected string $defaultNamespace = 'Subscriber';

    protected string $defaultDir = 'Subscriber';

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
            $io->errorStyle()->error('No subscriber name');

            return 255;
        }

        $this->codeGenerator->from($this->getViewPath('subscriber/*.tpl'))
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
