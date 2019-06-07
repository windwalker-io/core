<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Schedule;

use Cron\CronExpression;
use Windwalker\DI\Annotation\Inject;
use Windwalker\DI\Container;
use Windwalker\Queue\Job\JobInterface;

/**
 * The Schedule class.
 *
 * @method ScheduleEvent yearly($task)
 * @method ScheduleEvent annually($task)
 * @method ScheduleEvent monthly($task)
 * @method ScheduleEvent weekly($task)
 * @method ScheduleEvent daily($task)
 * @method ScheduleEvent hourly($task)
 * @method ScheduleEvent minutely($task)
 *
 * @since  3.5.3
 */
class Schedule
{
    /**
     * Property events.
     *
     * @var ScheduleEvent[]
     */
    protected $events = [];

    /**
     * Property container.
     *
     * @Inject()
     *
     * @var Container
     */
    protected $container;

    /**
     * Schedule constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        if (!class_exists(CronExpression::class)) {
            throw new \DomainException('Please install `dragonmantank/cron-expression ^2.3` first.');
        }

        $this->container = $container;
    }

    /**
     * getDueEvents
     *
     * @param array $tags
     *
     * @return  ScheduleEvent[]
     *
     * @since  3.5.3
     */
    public function getDueEvents(array $tags = []): array
    {
        return array_filter($this->getEvents($tags), function (ScheduleEvent $event) {
            return $event->isDue();
        });
    }

    /**
     * cron
     *
     * @param string $expression
     * @param mixed  $task
     *
     * @return  ScheduleEvent
     *
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     *
     * @since  3.5.3
     */
    public function cron(string $expression, $task): ScheduleEvent
    {
        return $this->events[] = $this->createEvent($expression, $task);
    }

    /**
     * always
     *
     * @param mixed $task
     *
     * @return  ScheduleEvent
     *
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     *
     * @since  3.5.6
     */
    public function always($task): ScheduleEvent
    {
        return $this->cron('@always', $task);
    }

    /**
     * perMinutes
     *
     * @param int   $minutes
     * @param mixed $task
     *
     * @return  static
     *
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     *
     * @since  3.5.3
     */
    public function perMinutes(int $minutes, $task): ScheduleEvent
    {
        return $this->cron(sprintf('*/%d * * * *', $minutes), $task);
    }

    /**
     * perHours
     *
     * @param int   $hours
     * @param mixed $task
     *
     * @return  ScheduleEvent
     *
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     *
     * @since  3.5.5
     */
    public function perHours(int $hours, $task): ScheduleEvent
    {
        return $this->cron(sprintf('0 */%d * * *', $hours), $task);
    }

    /**
     * hourlyAt
     *
     * @param int   $minute
     * @param mixed $task
     *
     * @return  static
     *
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     *
     * @since  3.5.3
     */
    public function hourlyAt(int $minute, $task): ScheduleEvent
    {
        return $this->cron(sprintf('%d * * * *', $minute), $task);
    }

    /**
     * perDays
     *
     * @param int   $days
     * @param mixed $task
     *
     * @return  ScheduleEvent
     *
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     *
     * @since  3.5.5
     */
    public function perDays(int $days, $task): ScheduleEvent
    {
        return $this->cron(sprintf('0 0 */%d * *', $days), $task);
    }

    /**
     * dailyAt
     *
     * @param int   $hour
     * @param mixed $task
     *
     * @return  ScheduleEvent
     *
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     *
     * @since  3.5.5
     */
    public function dailyAt(int $hour, $task): ScheduleEvent
    {
        return $this->cron(sprintf('0 %d * * *', $hour), $task);
    }

    /**
     * perMonths
     *
     * @param int   $months
     * @param mixed $task
     *
     * @return  ScheduleEvent
     *
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     *
     * @since  3.5.5
     */
    public function perMonths(int $months, $task): ScheduleEvent
    {
        return $this->cron(sprintf('0 0 1 */%d *', $months), $task);
    }

    /**
     * monthlyAt
     *
     * @param int   $day
     * @param mixed $task
     *
     * @return  ScheduleEvent
     *
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     *
     * @since  3.5.5
     */
    public function monthlyAt(int $day, $task): ScheduleEvent
    {
        return $this->cron(sprintf('0 0 %d * *', $day), $task);
    }

    /**
     * yearlyAt
     *
     * @param int   $month
     * @param mixed $task
     *
     * @return  ScheduleEvent
     *
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     *
     * @since  3.5.5
     */
    public function yearlyAt(int $month, $task): ScheduleEvent
    {
        return $this->cron(sprintf('0 0 1 %d *', $month), $task);
    }

    /**
     * createEvent
     *
     * @param string $expr
     * @param mixed  $task
     *
     * @return  ScheduleEvent
     *
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     *
     * @since  3.5.3
     */
    public function createEvent(string $expr, $task): ScheduleEvent
    {
        $handler = $task;

        if (is_string($task) && class_exists($task)) {
            $task = $this->container->newInstance($task);
        }

        if ($task instanceof JobInterface) {
            $handler = function () use ($task) {
                return $this->container->call([$task, 'execute']);
            };
        } elseif ($task instanceof \Closure) {
            $handler = function () use ($task) {
                return $this->container->call($task);
            };
        } elseif (is_object($task)) {
            $handler = function () use ($task) {
                return $this->container->call([$task, '__invoke']);
            };
        }

        return new ScheduleEvent($expr, $handler);
    }

    /**
     * Method to get property Events
     *
     * @param array $tags
     *
     * @return  ScheduleEvent[]
     *
     * @since  3.5.3
     */
    public function getEvents(array $tags = []): array
    {
        if ($tags === []) {
            return $this->events;
        }

        return array_filter($this->events, function (ScheduleEvent $event) use ($tags) {
            return array_intersect($event->getTags(), $tags) === $tags;
        });
    }

    /**
     * Method to set property events
     *
     * @param ScheduleEvent[] $events
     *
     * @return  static  Return self to support chaining.
     *
     * @since  3.5.3
     */
    public function setEvents(array $events)
    {
        $this->events = $events;

        return $this;
    }

    /**
     * createCronExpression
     *
     * @param string $expr
     *
     * @return  CronExpression
     *
     * @since  3.5.3
     */
    protected function createCronExpression(string $expr): CronExpression
    {
        return CronExpression::factory($expr);
    }

    /**
     * __call
     *
     * @param string $name
     * @param array  $args
     *
     * @return  mixed
     *
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     * @since  3.5.3
     */
    public function __call($name, $args)
    {
        $exprs = [
            'yearly' => '@yearly',
            'annually' => '@annually',
            'monthly' => '@monthly',
            'weekly' => '@weekly',
            'daily' => '@daily',
            'hourly' => '@hourly',
            'minutely' => '* * * * *',
        ];

        if (isset($exprs[strtolower($name)])) {
            $expr = $exprs[strtolower($name)];

            return $this->cron($expr, ...$args);
        }

        throw new \BadMethodCallException(
            sprintf(
                'Call to undefined method %s::%s()',
                __CLASS__,
                $name
            )
        );
    }
}
