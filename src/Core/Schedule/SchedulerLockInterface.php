<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule;

use Symfony\Component\Lock\LockInterface;

interface SchedulerLockInterface
{
    public function createLock(ScheduleEvent $event): LockInterface;
}
