<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

use InvalidArgumentException;

/**
 * Units for since(), until() and round(), named after the Temporal units.
 *
 * Weeks are not supported since DateInterval has no week field.
 */
enum DateTimeUnit: string
{
    case Year = 'year';
    case Month = 'month';
    case Day = 'day';
    case Hour = 'hour';
    case Minute = 'minute';
    case Second = 'second';
    case Millisecond = 'millisecond';
    case Microsecond = 'microsecond';

    /**
     * Accepts a case, its value or the plural form ("days").
     */
    public static function resolve(self|string $unit): self
    {
        if ($unit instanceof self) {
            return $unit;
        }

        $name = strtolower($unit);

        return self::tryFrom($name)
            ?? self::tryFrom(rtrim($name, 's'))
            ?? throw new InvalidArgumentException(sprintf('Invalid unit "%s".', $unit));
    }

    /**
     * 0 for the largest unit (Year), increasing for smaller units.
     */
    public function rank(): int
    {
        return match ($this) {
            self::Year => 0,
            self::Month => 1,
            self::Day => 2,
            self::Hour => 3,
            self::Minute => 4,
            self::Second => 5,
            self::Millisecond => 6,
            self::Microsecond => 7,
        };
    }

    public function isLargerThan(self $other): bool
    {
        return $this->rank() < $other->rank();
    }

    /**
     * Length in microseconds. Only fixed-length units (Day and below) have one.
     */
    public function microseconds(): int
    {
        return match ($this) {
            self::Day => 86_400_000_000,
            self::Hour => 3_600_000_000,
            self::Minute => 60_000_000,
            self::Second => 1_000_000,
            self::Millisecond => 1_000,
            self::Microsecond => 1,
            default => throw new InvalidArgumentException(
                sprintf('Unit "%s" has no fixed length.', $this->value)
            ),
        };
    }
}
