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
use Windwalker\Core\DateTime\Contract\HasCalendarFieldsInterface;
use Windwalker\Core\DateTime\Contract\HasChronosInterface;
use Windwalker\Core\DateTime\Contract\HasDateInterface;
use Windwalker\Core\DateTime\Contract\HasTimeInterface;

/**
 * A calendar date and wall-clock time without time zone, precision up to microsecond.
 */
final class PlainDateTime implements ChronosInterface, DifferenceInterface, RoundableInterface, HasDateInterface, HasCalendarFieldsInterface, HasTimeInterface, HasChronosInterface
{
    use ChronosValueTrait;
    use CalendarFieldsTrait;
    use DateFieldsTrait;
    use TimeFieldsTrait;

    /**
     * Always in UTC, so that no DST rule can ever affect the calculation.
     */
    private function __construct(private readonly DateTimeImmutable $dt) {}

    /**
     * @throws  \RangeException  When $overflow is Reject and a field is out of range.
     */
    public static function of(
        int $year,
        int $month,
        int $day,
        int $hour = 0,
        int $minute = 0,
        int $second = 0,
        int $microsecond = 0,
        DateOverflow $overflow = DateOverflow::Reject
    ): self {
        [$year, $month, $day] = ChronosMath::resolveDate($year, $month, $day, $overflow);
        [$hour, $minute, $second, $microsecond] = ChronosMath::resolveTime(
            $hour,
            $minute,
            $second,
            $microsecond,
            $overflow
        );

        return new self(
            new DateTimeImmutable('1970-01-01', new DateTimeZone('UTC'))
                ->setDate($year, $month, $day)
                ->setTime($hour, $minute, $second, $microsecond)
        );
    }

    public static function combine(PlainDate $date, PlainTime $time): self
    {
        return self::of(
            $date->getYear(),
            $date->getMonth(),
            $date->getDay(),
            $time->getHour(),
            $time->getMinute(),
            $time->getSecond(),
            $time->getMicrosecond(),
        );
    }

    /**
     * Take the wall-clock fields of the given object as is, without any time zone conversion.
     */
    public static function from(DateTimeInterface $date): self
    {
        return new self(
            new DateTimeImmutable($date->format('Y-m-d H:i:s.u'), new DateTimeZone('UTC'))
        );
    }

    /**
     * @param  string  $dateTime  `Y-m-d\TH:i[:s[.f]]`, the `T` may be lowercase or a space, the time part
     *                            may be omitted (midnight) and a UTC offset is accepted but ignored.
     */
    public static function parse(string $dateTime): self
    {
        $p = ChronosMath::parseIso($dateTime);

        if ($p === null) {
            throw new InvalidArgumentException(
                sprintf('Invalid date time "%s", expect Y-m-d\TH:i[:s[.f]].', $dateTime)
            );
        }

        return self::of(
            $p['year'],
            $p['month'],
            $p['day'],
            $p['hour'],
            $p['minute'],
            $p['second'],
            $p['microsecond']
        );
    }

    public static function now(DateTimeZone|string|null $tz = null): self
    {
        return self::from(Clock::now($tz));
    }

    public function with(
        ?int $year = null,
        ?int $month = null,
        ?int $day = null,
        ?int $hour = null,
        ?int $minute = null,
        ?int $second = null,
        ?int $microsecond = null,
        DateOverflow $overflow = DateOverflow::Constrain,
    ): self {
        return self::of(
            $year ?? $this->getYear(),
            $month ?? $this->getMonth(),
            $day ?? $this->getDay(),
            $hour ?? $this->getHour(),
            $minute ?? $this->getMinute(),
            $second ?? $this->getSecond(),
            $microsecond ?? $this->getMicrosecond(),
            $overflow
        );
    }

    /**
     * Replace the time part, midnight when $time is null.
     */
    public function withPlainTime(?PlainTime $time = null): self
    {
        return self::combine($this->toPlainDate(), $time ?? PlainTime::of());
    }

    public function withPlainDate(PlainDate $date): self
    {
        return self::combine($date, $this->toPlainTime());
    }

    public function startOfDay(): self
    {
        return $this->withPlainTime();
    }

    public function toPlainDate(): PlainDate
    {
        return PlainDate::of($this->getYear(), $this->getMonth(), $this->getDay());
    }

    public function toPlainYearMonth(): PlainYearMonth
    {
        return PlainYearMonth::of($this->getYear(), $this->getMonth());
    }

    public function toPlainMonthDay(): PlainMonthDay
    {
        return PlainMonthDay::of($this->getMonth(), $this->getDay());
    }

    public function toPlainTime(): PlainTime
    {
        return PlainTime::of($this->getHour(), $this->getMinute(), $this->getSecond(), $this->getMicrosecond());
    }

    /**
     * Interpret the wall-clock fields in the given time zone.
     */
    public function toDateTime(DateTimeZone|string|null $tz = null): DateTimeImmutable
    {
        $tz = is_string($tz) ? new DateTimeZone($tz) : ($tz ?? new DateTimeZone('UTC'));

        return new DateTimeImmutable($this->dt->format('Y-m-d H:i:s.u'), $tz);
    }

    public function toChronos(DateTimeZone|string|null $tz = null): Chronos
    {
        return Chronos::createFromInterface($this->toDateTime($tz));
    }

    public function compareTo(ChronosInterface $other): int
    {
        if (!$other instanceof self) {
            throw new \TypeError(sprintf('Cannot compare %s with %s.', self::class, get_debug_type($other)));
        }

        return $this->dt <=> $other->dt;
    }

    protected function differenceTo(
        ChronosInterface $other,
        DateTimeUnit|string|null $largestUnit,
        DateTimeUnit|string|null $smallestUnit,
        int $roundingIncrement,
        RoundingMode $roundingMode,
    ): DateInterval {
        return ChronosMath::difference(
            $this->dt,
            self::assertSame($other)->dt,
            $largestUnit,
            $smallestUnit,
            $roundingIncrement,
            $roundingMode,
            DateTimeUnit::Day,
            DateTimeUnit::Microsecond,
            DateTimeUnit::Year,
            DateTimeUnit::Microsecond,
        );
    }

    public function round(
        DateTimeUnit|string $smallestUnit,
        int $roundingIncrement = 1,
        RoundingMode $roundingMode = RoundingMode::HalfAwayFromZero,
    ): static {
        $unit = ChronosMath::assertUnit($smallestUnit, DateTimeUnit::Day, DateTimeUnit::Microsecond);

        $us = ChronosMath::roundMicros(ChronosMath::toMicros($this->dt), $unit, $roundingIncrement, $roundingMode);

        return new self(ChronosMath::fromMicros($us));
    }

    public function toIso(): string
    {
        return $this->dt->format($this->getMicrosecond() === 0 ? 'Y-m-d\TH:i:s' : 'Y-m-d\TH:i:s.u');
    }

    protected function applyFields(array $fields, DateOverflow $overflow): static
    {
        return new self(ChronosMath::addTo($this->dt, $fields, $overflow));
    }

    private static function assertSame(ChronosInterface $other): self
    {
        if (!$other instanceof self) {
            throw new \TypeError(sprintf('Cannot compare %s with %s.', self::class, get_debug_type($other)));
        }

        return $other;
    }

    private function format(string $format): string
    {
        return $this->dt->format($format);
    }
}
