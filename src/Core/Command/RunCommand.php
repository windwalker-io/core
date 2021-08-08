<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Windwalker\Console\CommandInterface;
use Windwalker\Console\CommandWrapper;
use Windwalker\Console\IOInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Console\CmdWrapper;
use Windwalker\Utilities\Arr;

use function Windwalker\cmd;

/**
 * The RunCommand class.
 */
#[CommandWrapper(description: 'Run custom scripts.')]
class RunCommand implements CommandInterface
{
    /**
     * RunCommand constructor.
     *
     * @param  ApplicationInterface  $app
     */
    public function __construct(protected ApplicationInterface $app)
    {
    }

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
            'names',
            InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
            'The script names to run.'
        );

        $command->addOption(
            'list',
            'l',
            InputOption::VALUE_OPTIONAL,
            'List available scripts.',
            false
        );

        $command->addOption(
            'ignore-error',
            'e',
            InputOption::VALUE_OPTIONAL,
            'Ignore error script and still run next.',
            false
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
        set_time_limit(0);

        $scripts = (array) $this->app->config('scripts');
        $names = $io->getArgument('names');

        if ($names === [] || $io->getOption('list') !== false) {
            $this->listScripts($scripts, $io);

            return 0;
        }

        foreach ($names as $name) {
            if (!isset($scripts[$name])) {
                throw new CommandNotFoundException(
                    sprintf(
                        "Script name: $name not found."
                    )
                );
            }

            $io->newLine();
            $io->writeln('Start Custom Script: <info>' . $name . '</info>');
            $io->writeln('---------------------------------------');

            $this->executeScript($scripts[$name], $io);
        }

        $io->newLine();
        $io->writeln('Complete script running.');

        return 0;
    }

    protected function executeScript(array $cmds, IOInterface $io): array
    {
        $result = [];

        foreach ($cmds as $cmd) {
            $result[] = $this->executeScriptCmd($cmd, $io);
        }

        return $result;
    }

    protected function executeScriptCmd(mixed $cmd, IOInterface $io): Process
    {
        $cmd = $this->prepareCmd($cmd);

        $process = $cmd($this->app);
        $e = $cmd->ignoreError;

        $io->writeln('>>> <info>' . $process->getCommandLine() . '</info>');
        $io->newLine();

        $process = $this->app->runProcess($process, null, true);

        if (!$e && !$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process;
    }

    /**
     * listScripts
     *
     * @param  array        $scripts
     * @param  IOInterface  $io
     *
     * @return  void
     */
    protected function listScripts(array $scripts, IOInterface $io)
    {
        if (!$scripts) {
            $io->writeln("\nNo custom scripts.\n");

            return;
        }

        $io->style()->title('Available Scripts');

        foreach ($scripts as $name => $script) {
            $io->writeln("<info>{$name}</info>");

            foreach ($script as $cmd) {
                $cmd = $this->prepareCmd($cmd);

                $input = $cmd->input;
                $process = $cmd($this->app);
                $cmd = $process->getCommandLine();

                $io->write('    <comment>$</comment> ' . $cmd);

                if ($input !== null) {
                    $io->write(sprintf('  <fg=cyan>(Input: %s)</>', preg_replace('/\s+/', ' ', $input)));
                }

                $io->newLine();
            }

            $io->newLine();
        }
    }

    protected function prepareCmd(mixed $cmd): CmdWrapper
    {
        if ($cmd instanceof \Closure || !$cmd instanceof CmdWrapper) {
            $cmd = cmd($cmd);
        }

        return $cmd;
    }
}
