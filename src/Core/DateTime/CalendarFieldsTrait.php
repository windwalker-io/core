<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

/**
 * Year-based calendar fields, see HasCalendarFieldsInterface.
 * The using class must provide `format(string $format): string`.
 */
trait CalendarFieldsTrait
{
    /**
     * @deprecated Zero-based, kept consistent with Chronos. Use `$dayOfTheYear`, which follows Temporal.
     *             The next major release will make `dayOfYear` the one-based Temporal version.
     */
    public string $dayOfYear {
        get => (string) $this->getDayOfYear();
    }

    public string $dayOfTheYear {
        get => (string) $this->getDayOfTheYear();
    }

    public string $weekOfYear {
        get => (string) $this->getWeekOfYear();
    }

    public string $yearOfWeek {
        get => $this->format('o');
    }

    public string $daysInYear {
        get => (string) $this->getDaysInYear();
    }

    public bool $inLeapYear {
        get => $this->isInLeapYear();
    }

    /**
     * @deprecated Zero-based like Chronos. Use `getDayOfTheYear()`.
     *             The next major release will make this the one-based Temporal version.
     */
    public function getDayOfYear(): int
    {
        return (int) $this->format('z');
    }

    public function getDayOfTheYear(): int
    {
        return (int) $this->format('z') + 1;
    }

    public function getWeekOfYear(): int
    {
        return (int) $this->format('W');
    }

    public function getYearOfWeek(): int
    {
        return (int) $this->format('o');
    }

    public function getDaysInYear(): int
    {
        return $this->isInLeapYear() ? 366 : 365;
    }

    public function isInLeapYear(): bool
    {
        return $this->format('L') === '1';
    }
}
