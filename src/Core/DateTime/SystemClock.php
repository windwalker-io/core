<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

class SystemClock implements ChronosClockInterface
{
    public function now(): Chronos
    {
        return new Chronos('now');
    }
}
