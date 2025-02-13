<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Schedule;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Utilities\Cache\InstanceCacheTrait;

/**
 * The ScheduleService class.
 */
class ScheduleService
{
    use InstanceCacheTrait;

    public function __construct(protected ApplicationInterface $app)
    {
    }

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

    protected function loadScheduleTasks(Schedule $schedule, string $route): void
    {
        $app = $this->app;

        include $route;
    }
}
