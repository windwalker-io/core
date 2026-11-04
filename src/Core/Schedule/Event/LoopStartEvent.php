<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule\Event;

use DateTimeImmutable;
use Windwalker\Core\Schedule\SchedulerRunner;
use Windwalker\Event\BaseEvent;

class LoopStartEvent extends BaseEvent
{
    public function __construct(
        public readonly SchedulerRunner $runner,
        public readonly DateTimeImmutable $time,
        public readonly int $run
    ) {}
}
