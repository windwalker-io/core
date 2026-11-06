<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime\Contract;

use RoundingMode;
use Windwalker\Core\DateTime\DateTimeUnit;

/**
 * Types that have a time part to round. PlainDate does not implement this since it has nothing smaller
 * than a day.
 */
interface RoundableInterface
{
    /**
     * Round to a multiple of $smallestUnit. Supported units: Instant and PlainTime from Hour,
     * PlainDateTime from Day, all down to Microsecond.
     *
     * PlainTime wraps around midnight (23:59:59.6 rounded to second is 00:00:00). Instant rounds
     * relative to the Unix epoch.
     *
     * @throws  \InvalidArgumentException  For an unsupported unit or a non-positive increment.
     */
    public function round(
        DateTimeUnit|string $smallestUnit,
        int $roundingIncrement = 1,
        RoundingMode $roundingMode = RoundingMode::HalfAwayFromZero,
    ): static;
}
