<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * A month and a day without year, time and time zone, e.g. a birthday or an annual holiday.
 *
 * Like Temporal it has no ordering and no arithmetic, because the order depends on the year.
 * It does not implement ChronosInterface for that reason.
 */
final class PlainMonthDay implements Stringable, JsonSerializable
{
    /**
     * A leap year, so that Feb 29 is valid.
     */
    private const int REFERENCE_YEAR = 1972;

    private function __construct(private readonly int $m, private readonly int $d) {}

    public string $month {
        get => sprintf('%02d', $this->m);
    }

    public string $day {
        get => sprintf('%02d', $this->d);
    }

    public static function of(int $month, int $day, DateOverflow $overflow = DateOverflow::Reject): self
    {
        [, $month, $day] = ChronosMath::resolveDate(self::REFERENCE_YEAR, $month, $day, $overflow);

        return new self($month, $day);
    }

    public static function from(DateTimeInterface|PlainDate|PlainDateTime $date): self
    {
        return $date instanceof DateTimeInterface
            ? self::of((int) $date->format('n'), (int) $date->format('j'))
            : self::of($date->getMonth(), $date->getDay());
    }

    /**
     * @param  string  $value  `--m-d` or `m-d`. A date or date time string is accepted too, the year is ignored.
     */
    public static function parse(string $value): self
    {
        if (preg_match('/^(?:--)?(\d{2})-(\d{2})\z/', $value, $m)) {
            try {
                return self::of((int) $m[1], (int) $m[2]);
            } catch (\RangeException) {
                // fall through to the exception below
            }
        } elseif (($parsed = ChronosMath::parseIso($value)) !== null) {
            return self::of($parsed['month'], $parsed['day']);
        }

        throw new InvalidArgumentException(sprintf('Invalid month day "%s", expect m-d.', $value));
    }

    public static function today(DateTimeZone|string|null $tz = null): self
    {
        return self::from(Clock::now($tz));
    }

    public function with(?int $month = null, ?int $day = null, DateOverflow $overflow = DateOverflow::Constrain): self
    {
        return self::of($month ?? $this->m, $day ?? $this->d, $overflow);
    }

    public function getMonth(): int
    {
        return $this->m;
    }

    public function getDay(): int
    {
        return $this->d;
    }

    /**
     * @param  DateOverflow  $overflow  Constrain turns Feb 29 into Feb 28 in a non-leap year, Reject throws.
     */
    public function toPlainDate(int $year, DateOverflow $overflow = DateOverflow::Constrain): PlainDate
    {
        return PlainDate::of($year, $this->m, $this->d, $overflow);
    }

    public function equals(self $other): bool
    {
        return $this->m === $other->m && $this->d === $other->d;
    }

    /**
     * ISO 8601 `--MM-DD` is not used, the output is `MM-DD` same as Temporal.
     */
    public function toIso(): string
    {
        return sprintf('%02d-%02d', $this->m, $this->d);
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
