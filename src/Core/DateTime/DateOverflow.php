<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

/**
 * How to handle a result that does not exist in the calendar,
 * e.g. `2026-01-31 + 1 month` or `with(day: 31)` in February.
 */
enum DateOverflow
{
    /**
     * Clamp to the last valid value: 2026-01-31 + 1 month = 2026-02-28.
     *
     * NOTICE: This differs from PHP native behavior, which overflows into the next month (2026-03-03).
     */
    case Constrain;

    /**
     * Throw an exception if the result is not a valid date.
     */
    case Reject;

    /**
     * Delegate to PHP native DateTime behavior, which overflows into the next month.
     */
    case Native;
}
