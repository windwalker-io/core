<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

class SystemClock implements ChronosClockInterface
{
    public function now(): Chronos
    {
        return new Chronos('now');
    }
}
