<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule;

use DateTimeImmutable;

interface SchedulerClaimInterface
{
    /**
     * Atomically retain a claim for this task's UTC minute.
     * Return false only when already claimed; backend errors must throw.
     */
    public function claim(ScheduleEvent $event, DateTimeImmutable $scheduledAt): bool;
}
