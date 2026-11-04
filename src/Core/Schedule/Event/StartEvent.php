<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule\Event;

use Windwalker\Core\Schedule\Schedule;
use Windwalker\Core\Schedule\SchedulerRunner;
use Windwalker\Event\BaseEvent;

class StartEvent extends BaseEvent
{
    public function __construct(
        public readonly SchedulerRunner $runner,
        public readonly Schedule $schedule
    ) {}
}
