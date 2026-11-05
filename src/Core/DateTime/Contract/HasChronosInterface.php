<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime\Contract;

use DateTimeZone;
use Windwalker\Core\DateTime\Chronos;

/**
 * Types that can be resolved to an exact moment. PlainTime does not implement this since it has no date.
 */
interface HasChronosInterface
{
    /**
     * @param  DateTimeZone|string|null  $tz  Instant: the zone to display in. Plain types: the zone in which
     *                                        the wall-clock fields are interpreted (a DST gap shifts forward
     *                                        and an ambiguous time takes the first occurrence, same as PHP).
     *                                        Chronos: converts to the zone, null keeps its own.
     *                                        Defaults to UTC for all types except Chronos.
     */
    public function toChronos(DateTimeZone|string|null $tz = null): Chronos;
}
