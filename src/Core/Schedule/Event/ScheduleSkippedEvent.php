<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule\Event;

use DateTimeImmutable;
use Windwalker\Core\Schedule\ScheduleEvent;
use Windwalker\Core\Schedule\ScheduleSkipReason;
use Windwalker\Core\Schedule\SchedulerRunner;
use Windwalker\Event\BaseEvent;

class ScheduleSkippedEvent extends BaseEvent
{
    public function __construct(
        public readonly SchedulerRunner $runner,
        public readonly ScheduleEvent $scheduleEvent,
        public readonly DateTimeImmutable $time,
        public readonly ScheduleSkipReason $reason
    ) {}
}
