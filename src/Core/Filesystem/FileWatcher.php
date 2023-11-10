<?php

declare(strict_types=1);

namespace Windwalker\Core\Filesystem;

use Spatie\Watcher\Watch;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Windwalker\Utilities\Options\OptionAccessTrait;

/**
 * The FileWatcher class.
 */
class FileWatcher extends Watch
{
    use OptionAccessTrait;

    protected ?Process $process = null;

    public function __construct()
    {
        parent::__construct();

        $this->options['usePolling'] = true;
    }

    public static function path(string $path): self
    {
        return (new static())->setPaths($path);
    }

    public static function paths(...$paths): self
    {
        return (new static())->setPaths($paths);
    }

    public function isRunning(): bool
    {
        return $this->process?->isRunning();
    }

    public function getChangedInfo(): string
    {
        return $this->process?->getIncrementalOutput();
    }

    public function hasChanged(): bool
    {
        return (bool) $this->getChangedInfo();
    }

    public function onFileChanged(callable $handler): self
    {
        $this->onFileCreated[] = $handler;
        $this->onFileUpdated[] = $handler;
        $this->onFileDeleted[] = $handler;

        return $this;
    }

    public function doActions(string $output): void
    {
        $this->actOnOutput($output);
    }

    public function listen(): static
    {
        $this->process = $this->getWatchProcess();

        return $this;
    }

    protected function getWatchProcess(): Process
    {
        $command = [
            (new ExecutableFinder())->find('node'),
            dirname(__DIR__, 3) . '/bin/file-watcher.js',
            json_encode($this->paths, JSON_THROW_ON_ERROR),
            json_encode($this->options, JSON_THROW_ON_ERROR),
        ];

        $process = new Process(
            command: $command,
            timeout: null,
        );

        $process->start();

        return $process;
    }

    public function stop(int $timeout = 10, int $signal = null): ?int
    {
        return $this->getRunningProcess()->stop($timeout, $signal);
    }

    public function getRunningProcess(): Process
    {
        if (!$this->process) {
            throw new \RuntimeException('Process not exists');
        }

        if (!$this->process->isRunning()) {
            throw new \RuntimeException('Process not running.');
        }

        return $this->process;
    }

    public function getProcess(): ?Process
    {
        return $this->process;
    }

    public function usePolling(bool $value): static
    {
        return $this->setOption('usePolling', $value);
    }

    public function awaitWriteFinish(int $stabilityThreshold = 2000, int $pollInterval = 100): static
    {
        return $this->setOption('awaitWriteFinish', compact('stabilityThreshold', 'pollInterval'));
    }

    public function disableAwaitWriteFinish(): static
    {
        unset($this->options['awaitWriteFinish']);

        return $this;
    }
}
