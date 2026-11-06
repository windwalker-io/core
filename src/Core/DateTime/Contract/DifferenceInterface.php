<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime\Contract;

use DateInterval;
use RoundingMode;
use Windwalker\Core\DateTime\DateTimeUnit;

/**
 * Types that can measure the difference to another value of the same type.
 *
 * Both methods return a DateInterval whose fields are all non-negative and whose `invert` flag carries
 * the sign, same as PHP native diff(). Unlike diff(), the units can be controlled:
 *
 * - $largestUnit: the largest unit of the result, defaults differ by type (Temporal defaults).
 * - $smallestUnit: the result is rounded to this unit.
 * - $roundingIncrement: round to a multiple of the smallest unit.
 * - $roundingMode: PHP native RoundingMode. TowardsZero is Temporal "trunc", HalfAwayFromZero is "halfExpand".
 *
 * Types that are not an exact point on the timeline (PlainDate, PlainTime, PlainDateTime) MUST throw
 * \TypeError for incompatible $other, same as compareTo(). \InvalidArgumentException is thrown for
 * units the type does not support, or when $largestUnit is smaller than $smallestUnit.
 */
interface DifferenceInterface
{
    /**
     * The duration from this to $other, positive if $other is later: `$a->until($b)` is `$b - $a`.
     */
    public function until(
        ChronosInterface $other,
        DateTimeUnit|string|null $largestUnit = null,
        DateTimeUnit|string|null $smallestUnit = null,
        int $roundingIncrement = 1,
        RoundingMode $roundingMode = RoundingMode::TowardsZero,
    ): DateInterval;

    /**
     * The duration from $other to this, positive if this is later: `$a->since($b)` is `$a - $b`.
     */
    public function since(
        ChronosInterface $other,
        DateTimeUnit|string|null $largestUnit = null,
        DateTimeUnit|string|null $smallestUnit = null,
        int $roundingIncrement = 1,
        RoundingMode $roundingMode = RoundingMode::TowardsZero,
    ): DateInterval;
}
