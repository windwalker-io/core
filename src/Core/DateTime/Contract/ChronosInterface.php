<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime\Contract;

use DateInterval;
use JsonSerializable;
use Stringable;
use Time\Duration;
use Windwalker\Core\DateTime\DateOverflow;

/**
 * Base contract of all modern date/time value objects.
 *
 * Comparison is only defined between values of the same type.
 * Implementations MUST throw \TypeError when the given value is incompatible.
 *
 * Implementations MAY widen the parameter type of compareTo(), equals(), isBefore() and isAfter().
 * Types that represent an exact point on the timeline (Instant, Chronos) accept any
 * ChronosInterface&HasInstantInterface or \DateTimeInterface. Types without a time zone
 * (PlainDate, PlainTime, PlainDateTime) MUST NOT accept \DateTimeInterface, since the result
 * would depend on an unknown time zone; convert explicitly first.
 *
 * equals() on exact-point types compares the same instant regardless of time zone, which is
 * consistent with PHP native `==`. Do not rely on `<`, `==` operators for value objects, use methods.
 */
interface ChronosInterface extends Stringable, JsonSerializable
{
    /**
     * @return  int         Negative if less than $other, 0 if equal, positive if greater.
     * @throws  \TypeError
     */
    public function compareTo(ChronosInterface $other): int;

    public function equals(ChronosInterface $other): bool;

    public function isBefore(ChronosInterface $other): bool;

    public function isAfter(ChronosInterface $other): bool;

    /**
     * Add an interval.
     *
     * NOTICE: By default, a result that does not exist in the calendar is clamped to the last valid day
     * (2026-01-31 + 1 month = 2026-02-28). PHP native add() overflows instead (2026-03-03).
     * Pass DateOverflow::Native to get the PHP native behavior.
     *
     * A Time\Duration has no calendar awareness, so implementations MUST convert it to a DateInterval
     * first and always calculate based on DateInterval.
     *
     * A string is converted to a DateInterval too: an ISO 8601 duration ("P1M") if it starts with "P",
     * otherwise DateInterval::createFromDateString() ("1 month 3 days"). Strings that produce
     * non-additive relative parts (e.g. "next monday", "last day of next month") MUST be rejected
     * with \InvalidArgumentException.
     */
    public function plus(Duration|DateInterval|string $interval, DateOverflow $overflow = DateOverflow::Constrain): static;

    /**
     * Subtract an interval. See plus() for the overflow behavior.
     */
    public function minus(Duration|DateInterval|string $interval, DateOverflow $overflow = DateOverflow::Constrain): static;

    /**
     * ISO 8601 representation.
     */
    public function toIso(): string;
}
