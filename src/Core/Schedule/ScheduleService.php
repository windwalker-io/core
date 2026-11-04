<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule;

use DateTimeImmutable;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\DateTime\Clock;
use Windwalker\Core\Schedule\Exception\ScheduleSkippedException;
use Windwalker\Utilities\Cache\InstanceCacheTrait;

/**
 * The ScheduleService class.
 */
class ScheduleService
{
    use InstanceCacheTrait;

    public function __construct(
        protected ApplicationInterface $app,
        protected SchedulerLockInterface $schedulerLock,
        protected SchedulerClaimInterface $schedulerClaim
    ) {}

    /**
     * @codeCoverageIgnore
     */
    public function getScheduleRoutes(): array
    {
        return (array) $this->app->config('schedules');
    }

    public function getSchedule(string|array|null $routes = null): Schedule
    {
        return $this->cacheStorage['instance'] ??= $this->createSchedule($routes);
    }

    public function createSchedule(string|array|null $routes = null): Schedule
    {
        $routes = $routes ?? $this->getScheduleRoutes();
        $routes = (array) $routes;

        $schedule = $this->app->make(Schedule::class);

        foreach ($routes as $route) {
            $this->loadScheduleTasks($schedule, $route);
        }

        return $schedule;
    }

    public function runEvent(ScheduleEvent $event, ?DateTimeImmutable $scheduledAt = null): mixed
    {
        $scheduledAt ??= Clock::now();
        $lock = $this->schedulerLock->createLock($event);

        if (!$lock->acquire(blocking: false)) {
            throw new ScheduleSkippedException(ScheduleSkipReason::LOCKED);
        }

        try {
            if (!$this->schedulerClaim->claim($event, $scheduledAt)) {
                throw new ScheduleSkippedException(ScheduleSkipReason::CLAIMED);
            }

            $handler = $event->getHandler();

            if (is_string($handler) && class_exists($handler)) {
                $handler = $this->app->make($handler);
            }

            return $this->app->call($handler);
        } finally {
            $lock->release();
        }
    }

    protected function loadScheduleTasks(Schedule $schedule, string $route): void
    {
        $app = $this->app;

        include $route;
    }
}
