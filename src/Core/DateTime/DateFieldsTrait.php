<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

/**
 * Date field accessors aligned with Chronos: properties are zero-padded strings, getters are integers.
 * The using class must provide `format(string $format): string`.
 */
trait DateFieldsTrait
{
    public string $year {
        get => $this->format('Y');
    }

    public string $month {
        get => $this->format('m');
    }

    public string $day {
        get => $this->format('d');
    }

    public string $dayOfWeek {
        get => $this->format('N');
    }

    public string $daysInMonth {
        get => $this->format('t');
    }

    public function getYear(): int
    {
        return (int) $this->format('Y');
    }

    public function getMonth(): int
    {
        return (int) $this->format('n');
    }

    public function getDay(): int
    {
        return (int) $this->format('j');
    }

    public function getDayOfWeek(): int
    {
        return (int) $this->format('N');
    }

    public function getDaysInMonth(): int
    {
        return (int) $this->format('t');
    }
}
