<?php

declare(strict_types=1);

namespace Windwalker\Core\Task;

use Symfony\Component\Process\Process;

/** @template T */
class TaskResult
{
    /** @param T $value */
    public function __construct(
        public readonly string $taskId,
        public readonly string|int|null $name,
        public readonly mixed $value,
        public readonly Process $process,
        public readonly bool $captureOutput
    ) {}

    public string $output {
        get => $this->process->getOutput();
    }

    public string $errorOutput {
        get => $this->process->getErrorOutput();
    }

    public ?int $exitCode {
        get => $this->process->getExitCode();
    }
}
