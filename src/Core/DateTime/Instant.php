<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use RoundingMode;
use Windwalker\Core\DateTime\Contract\ChronosInterface;
use Windwalker\Core\DateTime\Contract\DifferenceInterface;
use Windwalker\Core\DateTime\Contract\RoundableInterface;
use Windwalker\Core\DateTime\Contract\HasChronosInterface;
use Windwalker\Core\DateTime\Contract\HasInstantInterface;

/**
 * An exact point on the timeline, precision up to microsecond (the limit of PHP DateTime).
 *
 * It has no time zone and no calendar: only time-based intervals (hours, minutes, seconds)
 * can be added, years/months/days are rejected because their length depends on a time zone.
 */
final class Instant implements ChronosInterface, DifferenceInterface, RoundableInterface, HasInstantInterface, HasChronosInterface
{
    use ChronosValueTrait;

    public string $epochSeconds {
        get => (string) $this->dt->getTimestamp();
    }

    public string $microsecond {
        get => $this->dt->format('u');
    }

    public function getEpochSeconds(): int
    {
        return $this->dt->getTimestamp();
    }

    public function getMicrosecond(): int
    {
        return (int) $this->dt->format('u');
    }

    private function __construct(private readonly DateTimeImmutable $dt) {}

    public static function from(DateTimeInterface $date): self
    {
        return new self(
            DateTimeImmutable::createFromInterface($date)->setTimezone(new DateTimeZone('UTC'))
        );
    }

    public static function now(): self
    {
        return self::from(Clock::now());
    }

    public static function fromEpochSeconds(int $seconds, int $microsecond = 0): self
    {
        $seconds += (int) floor($microsecond / 1_000_000);
        $microsecond = $microsecond % 1_000_000;

        if ($microsecond < 0) {
            $microsecond += 1_000_000;
        }

        return new self(ChronosMath::fromMicros($seconds * 1_000_000 + $microsecond));
    }

    public static function fromEpochMicroseconds(int $microseconds): self
    {
        return new self(ChronosMath::fromMicros($microseconds));
    }

    public static function fromEpochMilliseconds(int $milliseconds): self
    {
        return self::fromEpochSeconds(
            (int) floor($milliseconds / 1000),
            ($milliseconds % 1000 + 1000) % 1000 * 1000
        );
    }

    /**
     * @param  string  $instant  Strict ISO 8601 with a time zone designator, e.g. `2026-01-01T00:00:00Z`
     *                           or `2026-01-01T08:00:00.123456+08:00`.
     */
    public static function parse(string $instant): self
    {
        $normalized = preg_replace_callback(
            '/[.,](\d+)(?=[zZ+-])/',
            static fn(array $m) => '.' . substr(str_pad($m[1], 6, '0'), 0, 6),
            strtoupper($instant)
        ) ?? $instant;

        foreach (['Y-m-d\TH:i:s.uP', 'Y-m-d\TH:i:sP'] as $format) {
            $dt = DateTimeImmutable::createFromFormat($format, $normalized);

            if ($dt !== false && DateTimeImmutable::getLastErrors() === false) {
                return self::from($dt);
            }
        }

        throw new InvalidArgumentException(sprintf('Invalid instant "%s", expect ISO 8601 with time zone.', $instant));
    }

    public function toInstant(): self
    {
        return $this;
    }

    public function toEpochMilliseconds(): int
    {
        return $this->getEpochSeconds() * 1000 + intdiv($this->getMicrosecond(), 1000);
    }

    public function toEpochMicroseconds(): int
    {
        return ChronosMath::toMicros($this->dt);
    }

    public function toDateTime(DateTimeZone|string $tz = 'UTC'): DateTimeImmutable
    {
        return $this->dt->setTimezone(is_string($tz) ? new DateTimeZone($tz) : $tz);
    }

    public function toChronos(DateTimeZone|string|null $tz = null): Chronos
    {
        return Chronos::createFromInterface($this->toDateTime($tz ?? 'UTC'));
    }

    /**
     * @throws  \TypeError  When $other is not an exact point on the timeline.
     */
    public function compareTo(ChronosInterface|DateTimeInterface $other): int
    {
        return $this->dt <=> $this->coerce($other)->dt;
    }

    private function coerce(ChronosInterface|DateTimeInterface $other): self
    {
        return match (true) {
            $other instanceof DateTimeInterface => self::from($other),
            $other instanceof HasInstantInterface => $other->toInstant(),
            default => throw new \TypeError(
                sprintf('Cannot compare %s with %s.', self::class, get_debug_type($other))
            ),
        };
    }

    protected function differenceTo(
        ChronosInterface|DateTimeInterface $other,
        DateTimeUnit|string|null $largestUnit,
        DateTimeUnit|string|null $smallestUnit,
        int $roundingIncrement,
        RoundingMode $roundingMode,
    ): DateInterval {
        return ChronosMath::difference(
            $this->dt,
            $this->coerce($other)->dt,
            $largestUnit,
            $smallestUnit,
            $roundingIncrement,
            $roundingMode,
            DateTimeUnit::Second,
            DateTimeUnit::Microsecond,
            DateTimeUnit::Hour,
            DateTimeUnit::Microsecond,
        );
    }

    public function round(
        DateTimeUnit|string $smallestUnit,
        int $roundingIncrement = 1,
        RoundingMode $roundingMode = RoundingMode::HalfAwayFromZero,
    ): static {
        $unit = ChronosMath::assertUnit($smallestUnit, DateTimeUnit::Hour, DateTimeUnit::Microsecond);

        ChronosMath::assertUnitIncrement($unit, $roundingIncrement, true);

        $us = ChronosMath::roundMicros(ChronosMath::toMicros($this->dt), $unit, $roundingIncrement, $roundingMode);

        return new self(ChronosMath::fromMicros($us));
    }

    public function toIso(): string
    {
        return $this->dt->format($this->getMicrosecond() === 0 ? 'Y-m-d\TH:i:s\Z' : 'Y-m-d\TH:i:s.u\Z');
    }

    protected function applyFields(array $fields, DateOverflow $overflow): static
    {
        ChronosMath::assertOnly($fields, ['h', 'i', 's', 'us'], self::class);

        return new self(ChronosMath::addTo($this->dt, $fields, $overflow));
    }
}
