<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

use DateInterval;
use DateTimeInterface;
use RoundingMode;
use Time\Duration;
use Windwalker\Core\DateTime\Contract\ChronosInterface;

/**
 * Shared behavior of the value objects. The using class must implement
 * compareTo(), toIso(), applyFields() and differenceTo().
 *
 * @mixin ChronosInterface
 */
trait ChronosValueTrait
{
    /**
     * @param  array{y: int, m: int, d: int, h: int, i: int, s: int, us: int}  $fields
     */
    abstract protected function applyFields(array $fields, DateOverflow $overflow): static;

    /**
     * The measure from this to $other, with the unit range of the using class.
     */
    abstract protected function differenceTo(
        ChronosInterface $other,
        DateTimeUnit|string|null $largestUnit,
        DateTimeUnit|string|null $smallestUnit,
        int $roundingIncrement,
        RoundingMode $roundingMode,
    ): DateInterval;

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

    public function until(
        ChronosInterface|DateTimeInterface $other,
        DateTimeUnit|string|null $largestUnit = null,
        DateTimeUnit|string|null $smallestUnit = null,
        int $roundingIncrement = 1,
        RoundingMode $roundingMode = RoundingMode::TowardsZero,
    ): DateInterval {
        return $this->differenceTo($other, $largestUnit, $smallestUnit, $roundingIncrement, $roundingMode);
    }

    public function since(
        ChronosInterface|DateTimeInterface $other,
        DateTimeUnit|string|null $largestUnit = null,
        DateTimeUnit|string|null $smallestUnit = null,
        int $roundingIncrement = 1,
        RoundingMode $roundingMode = RoundingMode::TowardsZero,
    ): DateInterval {
        return ChronosMath::negateInterval(
            $this->differenceTo(
                $other,
                $largestUnit,
                $smallestUnit,
                $roundingIncrement,
                ChronosMath::mirrorMode($roundingMode)
            )
        );
    }

    /**
     * @return  int  Negative if $a is before $b, 0 if equal, positive if after. Usable as `usort` callback.
     */
    public static function compare(ChronosInterface $a, ChronosInterface|DateTimeInterface $b): int
    {
        return $a->compareTo($b);
    }

    /**
     * PHP style alias of until() with the default units, and `days` is filled with the total
     * number of whole days like DateTime::diff(). $absolute drops the sign.
     */
    public function diff(ChronosInterface|DateTimeInterface $other, bool $absolute = false): DateInterval
    {
        $interval = $this->until($other);

        return DateInterval::__set_state([
            'y' => $interval->y,
            'm' => $interval->m,
            'd' => $interval->d,
            'h' => $interval->h,
            'i' => $interval->i,
            's' => $interval->s,
            'f' => $interval->f,
            'invert' => $absolute ? 0 : $interval->invert,
            'days' => $this->wholeDaysTo($other),
        ]);
    }

    private function wholeDaysTo(ChronosInterface|DateTimeInterface $other): int
    {
        try {
            $span = $this->until($other, DateTimeUnit::Day);

            return $span->d;
        } catch (\InvalidArgumentException) {
            // Types without a day unit (PlainTime, Instant) measure in hours.
            return intdiv($this->until($other, DateTimeUnit::Hour)->h, 24);
        }
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
