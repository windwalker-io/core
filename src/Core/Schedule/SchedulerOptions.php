<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule;

use DateTimeZone;

class SchedulerOptions
{
    public function __construct(
        public int $lifetime = 0,
        public array $names = [],
        public array $tags = [],
        public DateTimeZone|string|null $timezone = null,
        public bool $once = false,
        public int $maxRuns = 0
    ) {}
}
