<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule\Event;

use Throwable;
use Windwalker\Core\Schedule\ScheduleRunner;
use Windwalker\Event\BaseEvent;

class StopEvent extends BaseEvent
{
    public function __construct(
        public readonly ScheduleRunner $runner,
        public readonly string $reason,
        public readonly int $runs,
        public readonly ?Throwable $exception = null
    ) {}
}
