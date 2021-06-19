<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Console\Process;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Windwalker\Environment\PlatformHelper;

/**
 * Trait ProcessRunnerTrait
 */
trait ProcessRunnerTrait
{
    /**
     * createProcess
     *
     * @param  string|array  $script
     *
     * @return  Process
     *
     * @since  3.5.22
     */
    public function createProcess(string|array $script): Process
    {
        $process = is_array($script) ?
            new Process($script)
            : Process::fromShellCommandline($script);

        $process->setTimeout(0);

        $phpPath = dirname($this->getPhpBinary() ?: '/use/local/bin');

        $path = implode(
            PlatformHelper::isWindows() ? ';' : ':',
            [
                $phpPath,
                $this->path('@root/vendor/bin'),
                $this->path('@root/bin'),
                env('PATH') ?? env('Path'),
            ]
        );

        $env         = $process->getEnv();
        $env['PATH'] = $path;

        if (PlatformHelper::isWindows()) {
            $env['Path'] = $path;
        }

        $process->setEnv($env);

        $process->setWorkingDirectory($this->config('@root'));

        return $process;
    }

    protected function getProcessOutputCallback(?OutputInterface $output = null): callable
    {
        return static function () {
        };
    }

    /**
     * runProcess
     *
     * @param  string|array|Process           $process
     * @param  mixed                          $input
     * @param  bool|callable|OutputInterface  $output
     *
     * @return Process
     *
     * @since  3.5.5
     */
    public function runProcess(
        string|array|Process $process,
        mixed $input = null,
        bool|callable|OutputInterface $output = false
    ): Process {
        if (!$process instanceof Process) {
            $process = $this->createProcess($process);
        }

        if ($input !== null) {
            $process->setInput($input);
        }

        if ($output === true) {
            $output = $this->getProcessOutputCallback();
        } elseif ($output === false) {
            $output = null;
        } elseif ($output instanceof OutputInterface) {
            $output = $this->getProcessOutputCallback($output);
        }

        $process->run($output);

        return $process;
    }

    private function getPhpBinary(): string|false
    {
        return (new PhpExecutableFinder())->find();
    }
}
