<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Console\Process;

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

        $phpPath = dirname((new PhpExecutableFinder())->find());

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

    protected function getProcessOutputCallback(): callable
    {
        return static function () {
        };
    }

    /**
     * runProcess
     *
     * @param  string|array  $script
     * @param  mixed         $input
     * @param  bool          $output
     *
     * @return Process
     *
     * @since  3.5.5
     */
    public function runProcess(string|array $script, mixed $input = null, bool|callable $output = false): Process
    {
        $process = $this->createProcess($script);

        if ($input !== null) {
            $process->setInput($input);
        }

        if ($output === true) {
            $output = $this->getProcessOutputCallback();
        } elseif ($output === false) {
            $output = null;
        }

        $process->run($output);

        return $process;
    }
}
