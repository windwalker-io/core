<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

use Psr\Clock\ClockInterface;

interface ChronosClockInterface extends ClockInterface
{
    public function now(): Chronos;
}
