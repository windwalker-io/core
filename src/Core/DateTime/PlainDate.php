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
use Windwalker\Core\DateTime\Contract\HasCalendarFieldsInterface;
use Windwalker\Core\DateTime\Contract\HasChronosInterface;
use Windwalker\Core\DateTime\Contract\HasDateInterface;

/**
 * A calendar date without time and time zone.
 */
final class PlainDate implements ChronosInterface, DifferenceInterface, HasDateInterface, HasCalendarFieldsInterface, HasChronosInterface
{
    use ChronosValueTrait;
    use CalendarFieldsTrait;
    use DateFieldsTrait;

    /**
     * Always midnight in UTC, so that no DST rule can ever affect the calculation.
     */
    private function __construct(private readonly DateTimeImmutable $dt) {}

    /**
     * @throws  \RangeException  When $overflow is Reject and the date does not exist.
     */
    public static function of(int $year, int $month, int $day, DateOverflow $overflow = DateOverflow::Reject): self
    {
        [$year, $month, $day] = ChronosMath::resolveDate($year, $month, $day, $overflow);

        return new self(
            new DateTimeImmutable('1970-01-01', new DateTimeZone('UTC'))->setDate($year, $month, $day)
        );
    }

    /**
     * Take the wall-clock date of the given object as is, without any time zone conversion.
     */
    public static function from(DateTimeInterface $date): self
    {
        return self::of((int) $date->format('Y'), (int) $date->format('n'), (int) $date->format('j'));
    }

    /**
     * @param  string  $date  `Y-m-d`. A date time string is accepted too, its time and offset are ignored.
     */
    public static function parse(string $date): self
    {
        $parsed = ChronosMath::parseIso($date);

        if ($parsed === null) {
            throw new InvalidArgumentException(sprintf('Invalid date "%s", expect Y-m-d.', $date));
        }

        return self::of($parsed['year'], $parsed['month'], $parsed['day']);
    }

    public static function today(DateTimeZone|string|null $tz = null): self
    {
        return self::from(Clock::now($tz));
    }

    /**
     * Unlike PHP native setDate(), the default overflow is Constrain: with(day: 31) in February gives
     * Feb 28/29 instead of Mar 2/3.
     */
    public function with(
        ?int $year = null,
        ?int $month = null,
        ?int $day = null,
        DateOverflow $overflow = DateOverflow::Constrain,
    ): self {
        return self::of($year ?? $this->getYear(), $month ?? $this->getMonth(), $day ?? $this->getDay(), $overflow);
    }

    public function toPlainDate(): self
    {
        return $this;
    }

    public function toPlainYearMonth(): PlainYearMonth
    {
        return PlainYearMonth::of($this->getYear(), $this->getMonth());
    }

    public function toPlainMonthDay(): PlainMonthDay
    {
        return PlainMonthDay::of($this->getMonth(), $this->getDay());
    }

    public function toPlainDateTime(?PlainTime $time = null): PlainDateTime
    {
        return PlainDateTime::combine($this, $time ?? PlainTime::of(0, 0));
    }

    /**
     * Start of this day in the given time zone.
     */
    public function toDateTime(DateTimeZone|string|null $tz = null): DateTimeImmutable
    {
        $tz = is_string($tz) ? new DateTimeZone($tz) : ($tz ?? new DateTimeZone('UTC'));

        return new DateTimeImmutable($this->dt->format('Y-m-d') . ' 00:00:00', $tz);
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
        if (!$other instanceof self) {
            throw new \TypeError(sprintf('Cannot compare %s with %s.', self::class, get_debug_type($other)));
        }

        return ChronosMath::difference(
            $this->dt,
            $other->dt,
            $largestUnit,
            $smallestUnit,
            $roundingIncrement,
            $roundingMode,
            DateTimeUnit::Day,
            DateTimeUnit::Day,
            DateTimeUnit::Year,
            DateTimeUnit::Day,
        );
    }

    public function toIso(): string
    {
        return $this->dt->format('Y-m-d');
    }

    protected function applyFields(array $fields, DateOverflow $overflow): static
    {
        ChronosMath::assertOnly($fields, ['y', 'm', 'd'], self::class);

        return new self(ChronosMath::addTo($this->dt, $fields, $overflow));
    }

    private function format(string $format): string
    {
        return $this->dt->format($format);
    }
}
