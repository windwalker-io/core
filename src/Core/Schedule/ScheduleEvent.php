<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 .
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Schedule;

use Cron\CronExpression;
use DateTimeInterface;
use DateTimeZone;

use function Windwalker\nope;

/**
 * The ScheduleEvent class.
 *
 * @since  3.5.3
 */
class ScheduleEvent
{
    public const MINUTE_POSITION = 0;

    public const HOUR_POSITION = 1;

    public const DAY_POSITION = 2;

    public const MONTH_POSITION = 3;

    public const WEEK_POSITION = 4;

    /**
     * Property handler.
     *
     * @var callable
     */
    protected $handler;

    /**
     * Property expression.
     *
     * @var CronExpression
     */
    protected CronExpression $expression;

    /**
     * Property tags.
     *
     * @var  array
     */
    protected array $tags = [];

    /**
     * ScheduleEvent constructor.
     *
     * @param  string                 $name
     * @param  CronExpression|string  $expression
     * @param  callable|null          $handler
     */
    public function __construct(
        protected string $name,
        CronExpression|string $expression = '* * * * *',
        ?callable $handler = null
    ) {
        $this->handler = $handler;
        $this->setExpression($expression);
    }

    /**
     * execute
     *
     * @return  mixed
     *
     * @since  3.5.3
     */
    public function execute(): mixed
    {
        $handler = $this->handler;

        return $handler();
    }

    public function setPart(int $position, string $value): static
    {
        $this->getExpression()->setPart($position, $value);

        return $this;
    }

    /**
     * always
     *
     * @return  static
     *
     * @since  3.5.6
     */
    public function always(): static
    {
        return $this->setExpression('@always');
    }

    public function everyMinutes(?int $every = null, ?int $from = null, ?int $to = 59): static
    {
        return $this->setPart(static::MINUTE_POSITION, static::every($every, $from, $to));
    }

    public function minuteOfHour(int|string ...$v): static
    {
        return $this->setPart(static::MINUTE_POSITION, implode(',', $v));
    }

    public function everyHours(?int $every = null, ?int $from = null, ?int $to = 23): static
    {
        return $this->setPart(static::HOUR_POSITION, static::every($every, $from, $to))
            ->minuteOfHour(0);
    }

    public function hourOfDay(int|string ...$v): static
    {
        return $this->setPart(static::HOUR_POSITION, implode(',', $v));
    }

    public function everyDays(?int $every = null, ?int $from = null, ?int $to = 31): static
    {
        return $this->setPart(static::DAY_POSITION, static::every($every, $from, $to))
            ->hourOfDay(0)
            ->minuteOfHour(0);
    }

    public function dayOfMonth(int|string ...$v): static
    {
        return $this->setPart(static::DAY_POSITION, implode(',', $v));
    }

    public function everyMonths(?int $every = null, ?int $from = null, ?int $to = 12): static
    {
        return $this->setPart(static::MONTH_POSITION, static::every($every, $from, $to))
            ->dayOfMonth(1)
            ->hourOfDay(0)
            ->minuteOfHour(0);
    }

    public function monthOfYear(int|string ...$v): static
    {
        return $this->setPart(static::MONTH_POSITION, implode(',', $v));
    }

    public function everyWeeks(?int $every = null, ?int $from = null, ?int $to = 12): static
    {
        return $this->setPart(static::WEEK_POSITION, static::every($every, $from, $to))
            ->dayOfWeek(1)
            ->hourOfDay(0)
            ->minuteOfHour(0);
    }

    public function dayOfWeek(int|string ...$v): static
    {
        return $this->setPart(static::WEEK_POSITION, implode(',', $v));
    }

    public function minutely(): static
    {
        return $this->everyMinutes();
    }

    public function hourly(): static
    {
        return $this->setExpression('@hourly');
    }

    public function daily(): static
    {
        return $this->setExpression('@daily');
    }

    public function weekly(): static
    {
        return $this->setExpression('@weekly');
    }

    public function monthly(): static
    {
        return $this->setExpression('@monthly');
    }

    public function yearly(): static
    {
        return $this->setExpression('@yearly');
    }

    public function annually(): static
    {
        return $this->yearly();
    }

    protected static function every(?int $v = null, ?int $start = null, ?int $to = null): string
    {
        $sign = [];

        if ($start !== null && $to !== null) {
            $sign[0] = $start . '-' . $to;
        } else {
            $sign[0] = '*';
        }

        if ($v !== null) {
            $sign[1] = $v;
        } else {
            $sign[1] = '*';
        }

        return implode('/', array_unique($sign));
    }

    /**
     * isDue
     *
     * @param  DateTimeInterface|string  $currentTime
     * @param  DateTimeZone|string|null  $timeZone
     *
     * @return  bool
     *
     * @since  3.5.3
     */
    public function isDue(
        DateTimeInterface|string $currentTime = 'now',
        DateTimeZone|string|null $timeZone = null
    ): bool {
        if ($timeZone instanceof DateTimeZone) {
            $timeZone = $timeZone->getName();
        }

        return $this->expression->isDue($currentTime, $timeZone);
    }

    /**
     * tag
     *
     * @param  array|string  $tags
     *
     * @return  ScheduleEvent
     *
     * @since  3.5.3
     */
    public function tags(string ...$tags): self
    {
        $tags = array_merge($this->tags, $tags);

        $this->tags = array_unique($tags);

        return $this;
    }

    /**
     * Method to get property Handler
     *
     * @return  callable
     *
     * @since  3.5.3
     */
    public function getHandler(): callable
    {
        return $this->handler ?? nope();
    }

    /**
     * Method to set property handler
     *
     * @param  callable  $handler
     *
     * @return  static  Return self to support chaining.
     *
     * @since  3.5.3
     */
    public function handler(callable $handler): static
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * Method to get property Expression
     *
     * @return  CronExpression
     *
     * @since  3.5.3
     */
    public function getExpression(): CronExpression
    {
        return $this->expression;
    }

    /**
     * Method to set property expression
     *
     * @param  CronExpression|string  $expression
     *
     * @return  static  Return self to support chaining.
     *
     * @since  3.5.3
     */
    public function setExpression(CronExpression|string $expression): static
    {
        if ($expression === '@always') {
            $expression = new AlwaysExpression($expression);
        } elseif (is_string($expression)) {
            $expression = new CronExpression($expression);
        }

        $this->expression = $expression;

        return $this;
    }

    /**
     * Method to get property Tags
     *
     * @return  array
     *
     * @since  3.5.3
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * Method to set property tags
     *
     * @param  array  $tags
     *
     * @return  static  Return self to support chaining.
     *
     * @since  3.5.3
     */
    public function setTags(array $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Method to get property Name
     *
     * @return  string
     *
     * @since  3.5.6
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Method to set property name
     *
     * @param  string  $name
     *
     * @return  static  Return self to support chaining.
     *
     * @since  3.5.6
     */
    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->getExpression();
    }
}
