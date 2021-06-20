<?php

/**
 * Part of starter project.
 *
 * @copyright    Copyright (C) 2021 __ORGANIZATION__.
 * @license        __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Console\ConsoleApplication;

/**
 * The ServeCommand class.
 */
#[CommandWrapper(
    description: 'Start PHP Dev Server.'
)]
class ServeCommand implements CommandInterface
{
    /**
     * ServeCommand constructor.
     */
    public function __construct(protected ConsoleApplication $app)
    {
    }

    /**
     * configure
     *
     * @param    Command  $command
     *
     * @return    void
     */
    public function configure(Command $command): void
    {
        $command->addArgument(
            'host',
            InputArgument::OPTIONAL,
            'The server host.',
            'localhost:8000'
        );

        $command->addArgument(
            'route',
            InputArgument::OPTIONAL,
            'The server route file.',
        );

        $command->addOption(
            'docroot',
            't',
            InputOption::VALUE_REQUIRED,
            'The docroot dir path.'
        );

        $command->addOption(
            'port',
            'p',
            InputOption::VALUE_REQUIRED,
            'The server port.'
        );
    }

    /**
     * Executes the current command.
     *
     * @param    IOInterface  $io
     *
     * @return    int Return 0 is success, 1-255 is failure.
     */
    public function execute(IOInterface $io): int
    {
        $host = $io->getArgument('host');

        [$domain, $port] = explode(':', $host) + [null, null];

        $port ??= '8000';

        if ($io->getOption('port')) {
            $port = $io->getOption('port');
        }

        $host = $domain . ':' . $port;

        $args = [];

        $route = $io->getArgument('route');

        if ($route) {
            $args[] = $route;
        } else {
            $root = $io->getOption('docroot') ?? $this->app->path('@root') . '/www';
            $args[] = '-t';
            $args[] = $root;
        }

        $args = implode(' ', $args);

        $io->style()->title('Windwalker Dev Server');
        $io->writeln('Starting...');
        $io->newLine();
        $io->writeln('Index: http://' . $host);
        $io->writeln('Dev: http://' . $host . '/dev.php');

        $io->newLine(2);

        $this->app->runProcess(
            "php -S $host $args",
            '',
            function ($type, $buffer) use ($io) {
                if (str_contains($buffer, '[404]')) {
                    $buffer = "<fg=yellow>$buffer</>";
                }

                if (str_contains($buffer, '[500]')) {
                    $buffer = "<fg=red>$buffer</>";
                }

                $io->write($buffer);
            }
        );

        return 0;
    }
}
