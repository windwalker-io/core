<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime\Contract;

use Windwalker\Core\DateTime\Instant;

/**
 * The property is a string (same as Chronos), `getEpochSeconds()` returns an integer.
 */
interface HasInstantInterface
{
    /**
     * Seconds since the Unix epoch.
     */
    public string $epochSeconds { get; }

    public function getEpochSeconds(): int;

    public function toInstant(): Instant;
}
