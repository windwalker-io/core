<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule\Event;

use DateTimeImmutable;
use Throwable;
use Windwalker\Core\Schedule\ScheduleRunner;
use Windwalker\Event\BaseEvent;

class LoopEndEvent extends BaseEvent
{
    public function __construct(
        public readonly ScheduleRunner $runner,
        public readonly DateTimeImmutable $time,
        public readonly int $run,
        public readonly ?Throwable $exception = null
    ) {}
}
