<?php

declare(strict_types=1);

namespace Windwalker\Core\Queue;

use Windwalker\Queue\RunnerOptions;

class ScheduleWorkerOptions extends RunnerOptions
{
    public function __construct(
        bool $once = false,
        int $backoff = 0,
        bool $force = false,
        int $memoryLimit = 128,
        int|float|string $sleep = 1.0,
        int $tries = 5,
        int $timeout = 60,
        int $maxRuns = 0,
        int $lifetime = 0,
        bool $stopWhenEmpty = false,
        ?string $restartSignal = null,
        ?\Closure $controllerFactory = null,
        public bool $enqueuer = false
    ) {
        parent::__construct(
            once: $once,
            backoff: $backoff,
            force: $force,
            memoryLimit: $memoryLimit,
            sleep: $sleep,
            tries: $tries,
            timeout: $timeout,
            maxRuns: $maxRuns,
            lifetime: $lifetime,
            stopWhenEmpty: $stopWhenEmpty,
            restartSignal: $restartSignal,
            controllerFactory: $controllerFactory
        );
    }
}
