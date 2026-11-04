<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule\Exception;

use RuntimeException;
use Windwalker\Core\Schedule\ScheduleSkipReason;

class ScheduleSkippedException extends RuntimeException
{
    public function __construct(public readonly ScheduleSkipReason $reason)
    {
        parent::__construct($reason->value);
    }
}
