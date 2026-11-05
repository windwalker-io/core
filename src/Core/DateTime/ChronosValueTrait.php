<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

use DateInterval;
use DateTimeInterface;
use Time\Duration;
use Windwalker\Core\DateTime\Contract\ChronosInterface;

/**
 * Shared behavior of the value objects. The using class must implement
 * compareTo(), toIso() and applyFields().
 *
 * @mixin ChronosInterface
 */
trait ChronosValueTrait
{
    /**
     * @param  array{y: int, m: int, d: int, h: int, i: int, s: int, us: int}  $fields
     */
    abstract protected function applyFields(array $fields, DateOverflow $overflow): static;

    public function equals(ChronosInterface|DateTimeInterface $other): bool
    {
        return $this->compareTo($other) === 0;
    }

    public function isBefore(ChronosInterface|DateTimeInterface $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    public function isAfter(ChronosInterface|DateTimeInterface $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    public function plus(Duration|DateInterval|string $interval, DateOverflow $overflow = DateOverflow::Constrain): static
    {
        return $this->applyFields(ChronosMath::fields($interval), $overflow);
    }

    public function minus(Duration|DateInterval|string $interval, DateOverflow $overflow = DateOverflow::Constrain): static
    {
        return $this->applyFields(ChronosMath::negate(ChronosMath::fields($interval)), $overflow);
    }

    public function jsonSerialize(): string
    {
        return $this->toIso();
    }

    public function __toString(): string
    {
        return $this->toIso();
    }
}
