<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule\Event;

use Windwalker\Core\Schedule\Schedule;
use Windwalker\Core\Schedule\ScheduleRunner;
use Windwalker\Event\BaseEvent;

class StartEvent extends BaseEvent
{
    public function __construct(
        public readonly ScheduleRunner $runner,
        public readonly Schedule $schedule
    ) {}
}
