<?php

declare(strict_types=1);

namespace Windwalker\Core\Queue;

class ScheduleEnqueuerOptions
{
    public function __construct(
        public bool $once = false,
        public bool $force = false,
        public int $memoryLimit = 128,
        public float $sleep = 1.0,
        public int $timeout = 60,
        public int $maxRuns = 0,
        public bool $shuffleChannels = false,
        public bool $stopWhenEmpty = false
    ) {}
}
