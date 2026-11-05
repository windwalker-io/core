<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

use Psr\Clock\ClockInterface;

class Clock
{
    protected static ?ClockInterface $clock = null;

    public static function now(string|\DateTimeZone|null $tz = null): Chronos
    {
        self::$clock ??= new SystemClock();

        $date = new Chronos(self::$clock->now());

        if ($tz !== null) {
            $date = $date->setTimezone($tz);
        }

        return $date;
    }

    public static function instant(): Instant
    {
        return Instant::from(static::now());
    }

    public static function plainDate(string|\DateTimeZone|null $tz = null): PlainDate
    {
        return PlainDate::from(static::now($tz));
    }

    public static function plainTime(string|\DateTimeZone|null $tz = null): PlainTime
    {
        return PlainTime::from(static::now($tz));
    }

    public static function plainDateTime(string|\DateTimeZone|null $tz = null): PlainDateTime
    {
        return PlainDateTime::from(static::now($tz));
    }

    /**
     * @codeCoverageIgnore
     */
    public static function get(): ?ClockInterface
    {
        return self::$clock;
    }

    /**
     * @codeCoverageIgnore
     */
    public static function set(ClockInterface|\DateTimeInterface|string|int|float|null $clock): void
    {
        static::$clock = static::from($clock);
    }

    public static function from(ClockInterface|\DateTimeInterface|string|int|float|null $clock): ClockInterface
    {
        if ($clock === null) {
            $clock = new SystemClock();
        }

        if (!$clock instanceof ClockInterface) {
            $clock = new MockClock($clock);
        }

        return $clock;
    }

    /**
     * @codeCoverageIgnore
     */
    public static function reset(): void
    {
        self::$clock = null;
    }

    public static function isSystem(): bool
    {
        return self::$clock === null || self::$clock instanceof SystemClock;
    }
}
