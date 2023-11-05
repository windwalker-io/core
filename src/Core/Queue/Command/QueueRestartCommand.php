<?php

declare(strict_types=1);

namespace Windwalker\Core\Queue\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;

use function Windwalker\chronos;
use function Windwalker\fs;

/**
 * The QueueRestartCommand class.
 */
#[CommandWrapper(
    description: 'Send restart signal to all workers.'
)]
class QueueRestartCommand implements CommandInterface
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
            'time',
            InputArgument::OPTIONAL,
            'The time to restart all workers.',
            'now'
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
        $time = $io->getArgument('time');

        $file = fs(WINDWALKER_TEMP . '/queue/restart');
        $file->write((string) chronos($time)->toUnix());

        $io->writeln('Sent restart signal to all workers.');

        return 0;
    }
}
