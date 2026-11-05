<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime\Contract;

use Windwalker\Core\DateTime\PlainTime;

/**
 * Properties are zero-padded strings (same as Chronos), `get*()` methods return integers.
 */
interface HasTimeInterface
{
    public string $hour { get; }

    public string $minute { get; }

    public string $second { get; }

    /**
     * Six digits, e.g. "050000".
     */
    public string $microsecond { get; }

    public function getHour(): int;

    public function getMinute(): int;

    public function getSecond(): int;

    public function getMicrosecond(): int;

    public function toPlainTime(): PlainTime;
}
