<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\CliServer;

use Swoole\Process as SwooleProcess;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Process;
use Windwalker\Core\CliServer\Contracts\CliServerEngineInterface;
use Windwalker\Core\CliServer\Contracts\ServerProcessManageInterface;
use Windwalker\Core\Console\ConsoleApplication;
use Windwalker\Core\Console\Process\ProcessRunnerTrait;
use Windwalker\Core\Events\Console\MessageOutputTrait;
use Windwalker\DI\Attributes\Autowire;
use Windwalker\Utilities\Cache\InstanceCacheTrait;
use Windwalker\Utilities\Options\OptionsResolverTrait;

use function Windwalker\swoole_installed;

/**
 * The SwooleEngine class.
 */
class SwooleEngine implements CliServerEngineInterface, ServerProcessManageInterface
{
    use InstanceCacheTrait;
    use OptionsResolverTrait;
    use CliServerTrait;

    public function __construct(
        protected ConsoleApplication $app,
        protected ConsoleOutputInterface $output,
        #[Autowire]
        protected CliServerStateManager $serverStateManager,
        array $options = []
    ) {
        $this->resolveOptions($options, $this->configureOptions(...));
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->define('process_name')
            ->allowedTypes('string')
            ->default('windwalker_cli_server');

        $resolver->define('main')
            ->allowedTypes('string', 'null');

        $resolver->define('app')
            ->allowedTypes('string')
            ->default('main');

        $resolver->define('workers')
            ->allowedTypes('int', 'null');

        $resolver->define('task_workers')
            ->allowedTypes('int', 'null');

        $resolver->define('max_requests')
            ->allowedTypes('int', 'null');

        $resolver->define('watch')
            ->allowedTypes('bool');

        $resolver->define('state_file')
            ->allowedTypes('string')
            ->default(fn(Options $options) => 'tmp/swoole/state.json');
    }

    public static function isSupported(): bool
    {
        return swoole_installed();
    }

    public function run(string $host, int $port, array $options = []): int
    {
        $options = $this->getOptionsResolver()->resolve(
            array_merge(
                $this->options,
                $options
            )
        );

        $this->serverStateManager->setFilePath($options['state_file']);

        $output = $this->output;

        if ($this->isRunning()) {
            $output->getErrorOutput()->writeln('Server is already running.');

            return Command::FAILURE;
        }

        $state = $this->serverStateManager->createState($host, $port, $options);

        $process = $this->app->createProcess(
            [
                $this->getPhpBinary() ?: 'php',
                $options['main'],
                $this->serverStateManager->getFilePath(),
            ]
        );
        $process->setTty(true);

        $process->setEnv(
            [
                'APP_ENV' => env('APP_ENV'),
                'WINDWALKER_CLI_SERVER' => '1',
            ]
        );

        return $this->runServerProcess($process, $state);
    }

    protected function runServerProcess(Process $process, CliServerState $state): int
    {
        $options = $state->getManagerOptions();
        $process->start();

        while (!$process->isStarted()) {
            sleep(1);
        }

        $output = $this->getStyledOutput();

        $output->title('Windwalker Swoole Server');
        $output->writeln('Starting...');
        $output->newLine();

        if ($options['watch'] ?? null) {
            $watcher = $this->createFileWatcher($options['main']);
            $watcher->listen();
        }

        while ($process->isRunning()) {
            $output = $process->getIncrementalOutput();
            $errOutput = $process->getIncrementalErrorOutput();

            $process->clearOutput();
            $process->clearErrorOutput();

            if ($output !== '') {
                $this->output->write($output);
            }

            if ($errOutput) {
                $this->output->write($errOutput);
            }

            if (isset($watcher) && $watcher->hasChanged()) {
                $this->output->writeln('File changes detected. Restarting server.');

                $this->reloadServer();
            }

            usleep(1000 * 500); // 0.5 seconds
        }

        return 0;
    }

    public function isRunning(): bool
    {
        $state = $this->serverStateManager->getState();
        $masterPid = $state->getMasterPid();
        $managerPid = $state->getManagerPid();

        if ($managerPid) {
            return $masterPid && $managerPid && $this->isAlive($managerPid);
        }

        return $masterPid && $this->isAlive($masterPid);
    }

    public function reloadServer(): void
    {
        $state = $this->serverStateManager->getState();

        static::signal($state->getMasterPid(), SIGUSR1);
    }

    public function stopServer(): bool
    {
    }

    public function isAlive(int $pid): bool
    {
        return self::signal($pid);
    }

    public static function signal(int $pid, int $signo = 0): bool
    {
        return SwooleProcess::kill($pid, $signo);
    }

    /**
     * @return  SymfonyStyle
     */
    protected function getStyledOutput(): SymfonyStyle
    {
        return new SymfonyStyle(
            new ArrayInput([]),
            $this->output
        );
    }
}
