<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

class MockClock implements ChronosClockInterface
{
    protected Chronos $now;

    public function __construct(string|int|float|\DateTimeInterface $date)
    {
        $this->set($date);
    }

    public function now(): Chronos
    {
        return $this->now;
    }

    public function with(string|int|float|\DateTimeInterface $date): static
    {
        $clone = clone $this;
        $clone->set($date);

        return $clone;
    }

    public function set(string|int|float|\DateTimeInterface $date): static
    {
        if ($date instanceof \DateTimeInterface) {
            $this->now = Chronos::createFromInterface($date);
        } elseif (is_int($date)) {
            $this->now = Chronos::createFromTimestamp($date);
        } else {
            $this->now = new Chronos($date);
        }

        return $this;
    }

    public function modify(string $modifier): static
    {
        $this->now = $this->now->modify($modifier);

        return $this;
    }

    public function withModify(string $modifier): static
    {
        $new = clone $this;
        $new->modify($modifier);

        return $new;
    }

    public function sleep(int $seconds): static
    {
        $this->now = $this->now->modify("+{$seconds} seconds");

        return $this;
    }

    public function withSleep(int $seconds): static
    {
        $new = clone $this;
        $new->sleep($seconds);

        return $new;
    }

    public function usleep(int $microseconds): static
    {
        $this->now = $this->now->modify("+{$microseconds} microseconds");

        return $this;
    }

    public function withUsleep(int $microseconds): static
    {
        $new = clone $this;
        $new->usleep($microseconds);

        return $new;
    }
}
