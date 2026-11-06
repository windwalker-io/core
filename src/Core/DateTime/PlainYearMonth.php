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

/**
 * A year and a month without day, time and time zone, e.g. a credit card expiry or a billing period.
 *
 * Only years and months are supported by plus(), minus(), until() and since().
 */
final class PlainYearMonth implements ChronosInterface, DifferenceInterface
{
    use ChronosValueTrait;

    /**
     * Always the first day of the month at midnight in UTC.
     */
    private function __construct(private readonly DateTimeImmutable $dt) {}

    public string $year {
        get => $this->dt->format('Y');
    }

    public string $month {
        get => $this->dt->format('m');
    }

    public string $daysInMonth {
        get => $this->dt->format('t');
    }

    public string $daysInYear {
        get => $this->inLeapYear ? '366' : '365';
    }

    public string $monthsInYear {
        get => '12';
    }

    public bool $inLeapYear {
        get => $this->dt->format('L') === '1';
    }

    /**
     * @throws  \RangeException  When $overflow is Reject and the month is not 1 to 12.
     */
    public static function of(int $year, int $month, DateOverflow $overflow = DateOverflow::Reject): self
    {
        [$year, $month] = ChronosMath::resolveDate($year, $month, 1, $overflow);

        return new self(new DateTimeImmutable('1970-01-01', new DateTimeZone('UTC'))->setDate($year, $month, 1));
    }

    /**
     * Take the wall-clock year and month of the given object as is, without any time zone conversion.
     */
    public static function from(DateTimeInterface|PlainDate|PlainDateTime $date): self
    {
        return $date instanceof DateTimeInterface
            ? self::of((int) $date->format('Y'), (int) $date->format('n'))
            : self::of($date->getYear(), $date->getMonth());
    }

    /**
     * @param  string  $value  `Y-m`. A date or date time string is accepted too, the rest is ignored.
     */
    public static function parse(string $value): self
    {
        if (preg_match('/^(-?\d{4,6})-(\d{2})\z/', $value, $m)) {
            try {
                return self::of((int) $m[1], (int) $m[2]);
            } catch (\RangeException) {
                // fall through to the exception below
            }
        } elseif (($parsed = ChronosMath::parseIso($value)) !== null) {
            return self::of($parsed['year'], $parsed['month']);
        }

        throw new InvalidArgumentException(sprintf('Invalid year month "%s", expect Y-m.', $value));
    }

    public static function today(DateTimeZone|string|null $tz = null): self
    {
        return self::from(Clock::now($tz));
    }

    /**
     * The default overflow is Constrain: a month out of range is clamped to 1 to 12.
     */
    public function with(?int $year = null, ?int $month = null, DateOverflow $overflow = DateOverflow::Constrain): self
    {
        return self::of($year ?? $this->getYear(), $month ?? $this->getMonth(), $overflow);
    }

    public function getYear(): int
    {
        return (int) $this->dt->format('Y');
    }

    public function getMonth(): int
    {
        return (int) $this->dt->format('n');
    }

    public function getDaysInMonth(): int
    {
        return (int) $this->dt->format('t');
    }

    public function getDaysInYear(): int
    {
        return $this->inLeapYear ? 366 : 365;
    }

    public function getMonthsInYear(): int
    {
        return 12;
    }

    public function isInLeapYear(): bool
    {
        return $this->inLeapYear;
    }

    /**
     * @param  int           $day       Defaults to the first day.
     * @param  DateOverflow  $overflow  Constrain clamps to the last day of the month.
     */
    public function toPlainDate(int $day = 1, DateOverflow $overflow = DateOverflow::Constrain): PlainDate
    {
        return PlainDate::of($this->getYear(), $this->getMonth(), $day, $overflow);
    }

    public function endOfMonth(): PlainDate
    {
        return $this->toPlainDate($this->getDaysInMonth());
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
            DateTimeUnit::Year,
            DateTimeUnit::Month,
            DateTimeUnit::Year,
            DateTimeUnit::Month,
        );
    }

    /**
     * The days between the first days of the two months, for diff().
     */
    private function wholeDaysTo(ChronosInterface|DateTimeInterface $other): int
    {
        if (!$other instanceof self) {
            throw new \TypeError(sprintf('Cannot compare %s with %s.', self::class, get_debug_type($other)));
        }

        return $this->toPlainDate()->until($other->toPlainDate(), DateTimeUnit::Day)->d;
    }

    public function toIso(): string
    {
        return $this->dt->format('Y-m');
    }

    protected function applyFields(array $fields, DateOverflow $overflow): static
    {
        ChronosMath::assertOnly($fields, ['y', 'm'], self::class);

        return new self(ChronosMath::addTo($this->dt, $fields, $overflow));
    }
}
