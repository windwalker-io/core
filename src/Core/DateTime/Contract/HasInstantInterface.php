<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime\Contract;

use Windwalker\Core\DateTime\Instant;

/**
 * The property is a string (same as Chronos), `getEpochSecond()` returns an integer.
 */
interface HasInstantInterface
{
    /**
     * Seconds since the Unix epoch.
     */
    public string $epochSecond { get; }

    public function getEpochSecond(): int;

    public function toInstant(): Instant;
}
