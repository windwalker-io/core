<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime\Contract;

use Windwalker\Core\DateTime\PlainDate;

/**
 * Properties are zero-padded strings (same as Chronos), `get*()` methods return integers.
 */
interface HasDateInterface
{
    public string $year { get; }

    public string $month { get; }

    public string $day { get; }

    /**
     * ISO-8601 day of week, 1 (Monday) to 7 (Sunday).
     */
    public string $dayOfWeek { get; }

    public string $daysInMonth { get; }

    public function getYear(): int;

    public function getMonth(): int;

    public function getDay(): int;

    public function getDayOfWeek(): int;

    public function getDaysInMonth(): int;

    public function toPlainDate(): PlainDate;
}
