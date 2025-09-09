<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule;

use BadMethodCallException;
use Cron\CronExpression;
use DateTimeInterface;
use DateTimeZone;
use DomainException;
use Generator;
use ReflectionException;
use Windwalker\Core\DateTime\Clock;
use Windwalker\DI\Exception\DependencyResolutionException;

/**
 * The Schedule class.
 *
 * @method ScheduleEvent yearly(string $name)
 * @method ScheduleEvent annually(string $name)
 * @method ScheduleEvent monthly(string $name)
 * @method ScheduleEvent weekly(string $name)
 * @method ScheduleEvent daily(string $name)
 * @method ScheduleEvent hourly(string $name)
 * @method ScheduleEvent minutely(string $name)
 * @method ScheduleEvent always(string $name)
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
    protected array $events = [];

    /**
     * Schedule constructor.
     */
    public function __construct()
    {
        if (!class_exists(CronExpression::class)) {
            throw new DomainException('Please install `dragonmantank/cron-expression ^3.1` first.');
        }
    }

    /**
     * cron
     *
     * @param  string  $name
     * @param  string  $expression
     * @param  mixed   $handler
     *
     * @return  ScheduleEvent
     *
     * @since  3.5.3
     */
    public function cron(string $name, string $expression, mixed $handler): ScheduleEvent
    {
        return $this->events[$name] = $this->createEvent($name, $expression, $handler);
    }

    public function task(string $name, string $expr = '* * * * *', mixed $handler = null): ScheduleEvent
    {
        return $this->cron($name, $expr, $handler);
    }

    /**
     * getDueEvents
     *
     * @param  array                     $tags
     * @param  mixed                     $clock
     * @param  DateTimeZone|string|null  $timezone
     *
     * @return  Generator<ScheduleEvent>
     *
     * @since  3.5.3
     */
    public function getDueEvents(
        array $tags = [],
        mixed $clock = null,
        DateTimeZone|string|null $timezone = null
    ): Generator {
        $clock = Clock::from($clock);

        foreach ($this->getEvents($tags) as $name => $event) {
            if ($event->isDue($clock, $timezone)) {
                yield $name => $event;
            }
        }
    }

    /**
     * createEvent
     *
     * @param  string  $name
     * @param  string  $expr
     * @param  mixed   $handler
     *
     * @return  ScheduleEvent
     *
     * @since  3.5.3
     */
    public function createEvent(string $name, string $expr, mixed $handler = null): ScheduleEvent
    {
        return new ScheduleEvent($name, $expr, $handler);
    }

    /**
     * Method to get property Events
     *
     * @param  array  $tags
     *
     * @return  ScheduleEvent[]|Generator
     *
     * @since  3.5.3
     */
    public function getEvents(array $tags = []): Generator
    {
        foreach ($this->events as $name => $event) {
            if ($tags === [] || array_intersect($event->getTags(), $tags) === $tags) {
                yield $name => $event;
            }
        }
    }

    /**
     * Method to set property events
     *
     * @param  ScheduleEvent[]  $events
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
     * @param  string  $expr
     *
     * @return  CronExpression
     *
     * @since  3.5.3
     */
    protected function createCronExpression(string $expr): CronExpression
    {
        return new CronExpression($expr);
    }

    /**
     * __call
     *
     * @param  string  $name
     * @param  array   $args
     *
     * @return  mixed
     *
     * @throws ReflectionException
     * @throws DependencyResolutionException
     * @since  3.5.3
     */
    public function __call(string $name, array $args)
    {
        $exprs = [
            'yearly',
            'annually',
            'monthly',
            'weekly',
            'daily',
            'hourly',
            'minutely',
            'always',
        ];

        if (in_array(strtolower($name), $exprs)) {
            return $this->task($args[0])->$name();
        }

        throw new BadMethodCallException(
            sprintf(
                'Call to undefined method %s::%s()',
                __CLASS__,
                $name
            )
        );
    }
}
