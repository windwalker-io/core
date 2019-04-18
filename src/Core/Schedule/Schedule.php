<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Schedule;

use Cron\CronExpression;
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
 * @since  __DEPLOY_VERSION__
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
     * @since  __DEPLOY_VERSION__
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
     * @since  __DEPLOY_VERSION__
     */
    public function cron(string $expression, $task): ScheduleEvent
    {
        return $this->events[] = $this->createEvent($expression, $task);
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
     * @since  __DEPLOY_VERSION__
     */
    public function perMinutes(int $minutes, $task): ScheduleEvent
    {
        return $this->cron(sprintf('*/%d * * * *', $minutes), $task);
    }

    /**
     * hourlyAt
     *
     * @param int   $offset
     * @param mixed $task
     *
     * @return  static
     *
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     *
     * @since  __DEPLOY_VERSION__
     */
    public function hourlyAt(int $offset, $task): ScheduleEvent
    {
        return $this->cron(sprintf('%d * * * *', $offset), $task);
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
     * @since  __DEPLOY_VERSION__
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
     * @since  __DEPLOY_VERSION__
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
     * @since  __DEPLOY_VERSION__
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
     * @since  __DEPLOY_VERSION__
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
     * @since  __DEPLOY_VERSION__
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
