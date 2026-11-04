<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule;

use Symfony\Component\Lock\Exception\LockConflictedException;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Utilities\Cache\InstanceCacheTrait;

/**
 * The ScheduleService class.
 */
class ScheduleService
{
    use InstanceCacheTrait;

    public function __construct(
        protected ApplicationInterface $app,
        protected SchedulerLockInterface $schedulerLock
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

    public function runEvent(ScheduleEvent $event): mixed
    {
        $lock = $this->schedulerLock->createLock($event);

        if (!$lock->acquire(blocking: true)) {
            throw new LockConflictedException(sprintf('Unable to acquire schedule lock: %s', $event->getName()));
        }

        try {
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
