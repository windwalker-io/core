<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime\Contract;

/**
 * Year-based calendar fields. Properties are strings (same as Chronos), `get*()` methods return integers.
 *
 * NOTICE: `dayOfYear` starts from 0 to stay consistent with Chronos and is deprecated.
 * `dayOfTheYear` is the Temporal-style one-based value. The next major release will adopt the
 * Temporal version, i.e. `dayOfYear` will become one-based.
 */
interface HasCalendarFieldsInterface
{
    /**
     * Day of the year, starting from 0 (same as Chronos).
     *
     * @deprecated Use `$dayOfTheYear`. Will become one-based (Temporal style) in the next major release.
     */
    public string $dayOfYear { get; }

    /**
     * Day of the year, starting from 1 (Temporal `dayOfYear`).
     */
    public string $dayOfTheYear { get; }

    /**
     * ISO-8601 week number, 1 to 53.
     */
    public string $weekOfYear { get; }

    /**
     * The year that the ISO-8601 week belongs to, which may differ from `year` around New Year.
     */
    public string $yearOfWeek { get; }

    /**
     * 365 or 366.
     */
    public string $daysInYear { get; }

    /**
     * Always 12 (ISO calendar).
     */
    public string $monthsInYear { get; }

    /**
     * Always 7 (ISO calendar).
     */
    public string $daysInWeek { get; }

    public bool $inLeapYear { get; }

    /**
     * @deprecated Zero-based. Use `getDayOfTheYear()`.
     */
    public function getDayOfYear(): int;

    public function getDayOfTheYear(): int;

    public function getWeekOfYear(): int;

    public function getYearOfWeek(): int;

    public function getDaysInYear(): int;

    public function getMonthsInYear(): int;

    public function getDaysInWeek(): int;

    public function isInLeapYear(): bool;
}
