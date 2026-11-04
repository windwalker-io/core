<?php

declare(strict_types=1);

namespace Windwalker\Core\Task;

use Closure;

class TaskRunnerOptions
{
    /** @param null|Closure(string|int|null, string, string): void $onOutput */
    public function __construct(
        public ?float $timeout = 60,
        public ?Closure $onOutput = null,
        public bool $captureOutput = true,
        public string|int|null $name = null,
        public int $resultTtl = 300
    ) {}
}
