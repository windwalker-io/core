<?php

declare(strict_types=1);

namespace Windwalker\Core\Task;

use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class TaskExecutionException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $exitCode,
        public readonly string $stderr,
        public readonly ?string $remoteClass = null,
        int $code = 0,
        public readonly ?Process $process = null,
        public readonly ?string $taskId = null,
        public readonly string|int|null $name = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
