<?php

declare(strict_types=1);

namespace Windwalker\Core\Schedule;

use Cron\CronExpression;

class ScheduleExpression
{
    public const int MINUTE_POSITION = 0;

    public const int HOUR_POSITION = 1;

    public const int DAY_POSITION = 2;

    public const int MONTH_POSITION = 3;

    public const int WEEK_POSITION = 4;

    /**
     * Property expression.
     *
     * @var CronExpression
     */
    protected CronExpression $expression;

    /**
     * @param  CronExpression|string  $expression
     */
    public function __construct(CronExpression|string $expression = '* * * * *')
    {
        $this->setExpression($expression);
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
        return $this->setPart(static::HOUR_POSITION, implode(',', $v))
            ->minuteOfHour(0);
    }

    public function everyDays(?int $every = null, ?int $from = null, ?int $to = 31): static
    {
        return $this->setPart(static::DAY_POSITION, static::every($every, $from, $to))
            ->hourOfDay(0);
    }

    public function dayOfMonth(int|string ...$v): static
    {
        return $this->setPart(static::DAY_POSITION, implode(',', $v))
            ->hourOfDay(0);
    }

    public function everyMonths(?int $every = null, ?int $from = null, ?int $to = 12): static
    {
        return $this->setPart(static::MONTH_POSITION, static::every($every, $from, $to))
            ->dayOfMonth(1);
    }

    public function monthOfYear(int|string ...$v): static
    {
        return $this->setPart(static::MONTH_POSITION, implode(',', $v))
            ->dayOfMonth(1);
    }

    public function everyWeeks(?int $every = null, ?int $from = null, ?int $to = 12): static
    {
        return $this->setPart(static::WEEK_POSITION, static::every($every, $from, $to))
            ->hourOfDay(0);
    }

    public function dayOfWeek(int|string ...$v): static
    {
        return $this->setPart(static::WEEK_POSITION, implode(',', $v))
            ->hourOfDay(0);
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
            $expression = '* * * * *';
        }

        if (is_string($expression)) {
            $expression = new CronExpression($expression);
        }

        $this->expression = $expression;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->getExpression();
    }
}
