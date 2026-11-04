<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule\Event;

use DateTimeImmutable;
use Windwalker\Core\Schedule\ScheduleEvent;
use Windwalker\Core\Schedule\ScheduleRunner;
use Windwalker\Event\BaseEvent;

class BeforeScheduleRunEvent extends BaseEvent
{
    public function __construct(
        public readonly ScheduleRunner $runner,
        public readonly ScheduleEvent $scheduleEvent,
        public readonly DateTimeImmutable $time
    ) {}
}
