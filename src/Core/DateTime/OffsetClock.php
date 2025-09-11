<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

use Psr\Clock\ClockInterface;

class OffsetClock implements ChronosClockInterface
{
    protected ClockInterface $baseClock;

    protected int $offsetSeconds = 0;

    public function __construct(mixed $baseClock)
    {
        $this->baseClock = Clock::from($baseClock);
    }

    public function now(): Chronos
    {
        return $this->baseClock->now()->addSeconds($this->offsetSeconds);
    }

    public function addSeconds(int $seconds): static
    {
        $this->offsetSeconds += $seconds;

        return $this;
    }

    public function withAddedSeconds(int $seconds): static
    {
        $clone = clone $this;
        $clone->addSeconds($seconds);

        return $clone;
    }
}
