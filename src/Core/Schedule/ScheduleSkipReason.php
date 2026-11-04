<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule;

enum ScheduleSkipReason: string
{
    case LOCKED = 'Task is already running.';
    case CLAIMED = 'Scheduled minute has already been claimed.';
}
