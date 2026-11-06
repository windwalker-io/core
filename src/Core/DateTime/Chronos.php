<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Psr\Clock\ClockInterface;
use SebastianBergmann\Invoker\TimeoutException;
use Time\Duration;
use Windwalker\Core\DateTime\Contract\ChronosInterface;
use Windwalker\Core\DateTime\Contract\HasChronosInterface;
use Windwalker\Core\DateTime\Contract\HasDateInterface;
use Windwalker\Core\DateTime\Contract\HasInstantInterface;
use Windwalker\Core\DateTime\Contract\HasTimeInterface;

/**
 * The Chronos class.
 */
class Chronos extends DateTimeImmutable implements
    ChronosInterface,
    HasDateInterface,
    HasTimeInterface,
    HasInstantInterface,
    HasChronosInterface
{
    use DateFieldsTrait;
    use TimeFieldsTrait;

    /**
     * Day of the year, starting from 0.
     */
    public string $dayOfYear {
        get => $this->format('z');
    }

    public bool $isLeapYear {
        get => (bool) $this->format('L');
    }

    /**
     * English ordinal suffix of the day, e.g. "st".
     */
    public string $ordinal {
        get => $this->format('S');
    }

    /**
     * ISO-8601 week number.
     */
    public string $week {
        get => $this->format('W');
    }

    public string $epochSecond {
        get => (string) $this->getTimestamp();
    }

    public const string FORMAT_YMD = 'Y-m-d';

    public const string FORMAT_YMD_HI = 'Y-m-d H:i';

    public const string FORMAT_YMD_HIS = 'Y-m-d H:i:s';

    /**
     * The format string to be applied when using the __toString() magic method.
     *
     * @var  string
     * @since  2.1
     */
    public static string $format = 'Y-m-d H:i:s';

    /**
     * @param  mixed                     $date
     * @param  string|DateTimeZone|null  $tz
     *
     * @return  static
     *
     * @throws  Exception
     * @since  3.5.19
     */
    public static function wrap(mixed $date = null, string|DateTimeZone|null $tz = null): static
    {
        if ($date instanceof static) {
            return $date;
        }

        if ($date instanceof DateTimeInterface) {
            return static::createFromInterface($date);
        }

        if ($date instanceof HasChronosInterface) {
            return static::createFromInterface($date->toChronos($tz));
        }

        return static::create($date, $tz);
    }

    /**
     * @param  mixed                     $date
     * @param  string|DateTimeZone|null  $tz
     *
     * @return  static|null
     *
     * @deprecated  Use tryWrap() instead.
     */
    public static function wrapOrNull(mixed $date = null, string|DateTimeZone|null $tz = null): ?static
    {
        return static::tryWrap($date, $tz);
    }

    public static function tryWrap(mixed $date = 'now', string|DateTimeZone|null $tz = null): ?static
    {
        if ($date === null || $date === '') {
            return null;
        }

        return static::wrap($date, $tz);
    }

    /**
     * Constructor.
     *
     * @param  string                    $date  String in a format accepted by strtotime(), defaults to "now".
     * @param  string|DateTimeZone|null  $tz    Time zone to be used for the date. Might be a string or
     *                                          a DateTimeZone object.
     *
     * @throws  Exception
     * @since   2.1
     */
    public function __construct(mixed $date = null, string|DateTimeZone|null $tz = null)
    {
        if ($date instanceof ClockInterface) {
            $date = $date->now();
        }

        if ($date === null) {
            $date = Clock::now($tz);
        }

        $tz = static::wrapTimezoneObject($tz);

        // If the date is numeric assume a unix timestamp and convert it.
        $date = is_numeric($date) ? date('c', (int) $date) : $date;

        if ($date instanceof DateTimeInterface) {
            $tz ??= $date->getTimezone();
            $date = $date->format('Y-m-d H:i:s.u');
        }

        // Call the DateTime constructor.
        parent::__construct($date, $tz);
    }

    public static function wrapTimezoneObject(string|DateTimeZone|null $tz = null): ?DateTimeZone
    {
        if (is_string($tz)) {
            $tz = new DateTimeZone($tz);
        }

        return $tz;
    }

    /**
     * toFormat
     *
     * @param  string|DateTimeInterface  $date
     * @param  string                    $format
     *
     * @return  string
     *
     * @throws  Exception
     * @since  3.2
     */
    public static function toFormat(string|DateTimeInterface $date, string $format): string
    {
        if (is_string($date)) {
            $date = new static($date);
        }

        return $date->format($format);
    }

    /**
     * current
     *
     * @param  string                    $format
     * @param  string|DateTimeZone|null  $tz
     *
     * @return  string
     * @throws  Exception
     *
     * @deprecated  Use Clock::now()->format() instead.
     */
    #[\Deprecated(message: 'Use Clock::now()->format() instead.', since: '4.2')]
    public static function now(string $format = self::FORMAT_YMD_HIS, string|DateTimeZone|null $tz = null): string
    {
        return Clock::now()->format($format, $tz);
    }

    /**
     * @codeCoverageIgnore
     */
    public static function clockNow(): Chronos
    {
        return Clock::now();
    }

    /**
     * Proxy for new DateTime.
     *
     * @param  string                    $date  String in a format accepted by strtotime(), defaults to "now".
     * @param  string|DateTimeZone|null  $tz    Time zone to be used for the date.
     *
     * @return  static
     *
     * @throws  Exception
     * @since   2.1
     *
     * @codeCoverageIgnore
     */
    public static function create(mixed $date = null, string|DateTimeZone|null $tz = null): static
    {
        return new static($date, $tz);
    }

    /**
     * Parse a string into a new DateTime object according to the specified format
     *
     * @param  string  $format    Format accepted by date().
     * @param  string  $time      String representing the time.
     * @param  mixed   $timezone  A DateTimeZone object representing the desired time zone.
     *
     * @return  static|bool
     * @throws  Exception
     */
    public static function createFromFormat($format, $time, $timezone = null): static|false
    {
        $timezone = static::wrapTimezoneObject($timezone);

        $datetime = parent::createFromFormat($format, $time, $timezone);

        if (!$datetime) {
            return false;
        }

        $chronos = new static($datetime->getTimestamp(), $datetime->getTimezone());
        $chronos = $chronos->setTime(
            (int) $datetime->format('H'),
            (int) $datetime->format('i'),
            (int) $datetime->format('s'),
            (int) $datetime->format('u')
        );

        return $chronos;
    }

    /**
     * createInterval
     *
     * @param  string|int  $duration  Can be these formats:
     *                                - 30 (int) Will be 30 seconds.
     *                                - `PT30S` DateInterval format, see:
     *                                https://www.php.net/manual/en/dateinterval.construct.php
     *                                - `30seconds` DateTime format.
     *
     * @return  DateInterval
     *
     * @throws  Exception
     */
    public static function createInterval(\DateInterval|string|int $duration): DateInterval
    {
        if ($duration instanceof \DateInterval) {
            return $duration;
        }

        if (is_int($duration)) {
            $duration = 'PT' . $duration . 'S';
        }

        if (str_starts_with($duration, 'P')) {
            return new DateInterval($duration);
        }

        return DateInterval::createFromDateString($duration);
    }

    /**
     * @see https://stackoverflow.com/a/22381301/8134785
     *
     * @param  DateInterval|string  $interval
     *
     * @return  int
     * @throws  Exception
     */
    public static function intervalToSeconds(DateInterval|string $interval): int
    {
        if (!$interval instanceof DateInterval) {
            $interval = new DateInterval($interval);
        }

        return (int) ($interval->format('%r') . (
            ($interval->s)
                + (60 * ($interval->i))
                + (60 * 60 * ($interval->h))
                + (24 * 60 * 60 * ($interval->d))
                + (30 * 24 * 60 * 60 * ($interval->m))
                + (365 * 24 * 60 * 60 * ($interval->y))
        ));
    }

    public static function wrapDuration(
        Duration|\DateInterval|string|int $duration
    ): Duration {
        if ($duration instanceof \DateInterval) {
            $duration = static::intervalToSeconds($duration);
        }

        if (is_int($duration)) {
            return Duration::fromSeconds($duration);
        }

        if (is_string($duration)) {
            if (str_starts_with($duration, 'P')) {
                return Duration::fromIso8601DurationString($duration);
            } else {
                return Duration::fromSeconds(
                    static::intervalToSeconds(DateInterval::createFromDateString($duration))
                );
            }
        }

        return $duration;
    }

    public static function tryDuration(
        Duration|\DateInterval|string|int|null $duration
    ): ?Duration {
        if ($duration === null) {
            return null;
        }

        try {
            return static::wrapDuration($duration);
        } catch (TimeoutException) {
            return null;
        }
    }

    /**
     * @param  string|int|DateTimeInterface  $start
     * @param  string|int|DateInterval       $interval
     * @param  string|int|DateTimeInterface  $end
     * @param  int                           $options
     *
     * @return  \DatePeriod
     *
     * @throws  Exception
     */
    public static function createPeriodBetween(
        string|int|DateTimeInterface $start,
        string|int|DateInterval $interval,
        string|int|DateTimeInterface $end,
        int $options = 0
    ): \DatePeriod {
        $start = static::wrap($start);
        $end = static::wrap($end);

        return new \DatePeriod(
            $start,
            static::createInterval($interval),
            $end,
            $options
        );
    }

    /**
     * @param  string|int|DateTimeInterface  $start
     * @param  string|int|DateInterval       $interval
     * @param  int                           $recurrences
     * @param  int                           $options
     *
     * @return  \DatePeriod
     *
     * @throws  Exception
     */
    public static function createPeriodRecurrences(
        string|int|DateTimeInterface $start,
        string|int|DateInterval $interval,
        int $recurrences,
        int $options = 0
    ): \DatePeriod {
        $start = static::wrap($start);

        if (!$interval instanceof DateInterval) {
            $interval = static::createInterval($interval);
        }

        return new \DatePeriod(
            $start,
            $interval,
            $recurrences,
            $options
        );
    }

    /**
     * @param  string|int|DateTimeInterface  $end
     * @param  string|int|DateInterval       $interval
     * @param  int                           $options
     *
     * @return  \DatePeriod
     *
     * @throws  Exception
     *
     * @codeCoverageIgnore
     */
    public function getPeriodTo(
        string|int|DateInterval $interval,
        string|int|DateTimeInterface $end,
        int $options = 0
    ): \DatePeriod {
        return static::createPeriodBetween($this, $interval, $end, $options);
    }

    /**
     * @param  int                      $recurrences
     * @param  string|int|DateInterval  $interval
     * @param  int                      $options
     *
     * @return  \DatePeriod
     *
     * @throws  Exception
     *
     * @codeCoverageIgnore
     */
    public function getPeriodRecurrences(
        string|int|DateInterval $interval,
        int $recurrences,
        int $options = 0
    ): \DatePeriod {
        return static::createPeriodRecurrences($this, $interval, $recurrences, $options);
    }

    /**
     * @inheritDoc
     */
    public function format($format, string|DateTimeZone|null $tz = null): string
    {
        if (!$tz) {
            return parent::format($format);
        }

        $new = clone $this;
        $new = $new->setTimezone($tz);

        return $new->format($format);
    }

    /**
     * @codeCoverageIgnore
     */
    public function isFuture(): bool
    {
        return $this->isGt('now');
    }

    /**
     * @codeCoverageIgnore
     */
    public function isPast(): bool
    {
        return $this->isLt('now');
    }

    /**
     * Is greater than.
     */
    public function isGt(\DateTimeInterface|ClockInterface|string|int $now = 'now'): bool
    {
        return $this > static::wrap($now);
    }

    /**
     * Is greater than or equal to.
     */
    public function isGte(\DateTimeInterface|ClockInterface|string|int $now = 'now'): bool
    {
        return $this >= static::wrap($now);
    }

    /**
     * Is less than.
     */
    public function isLt(\DateTimeInterface|ClockInterface|string|int $now = 'now'): bool
    {
        return $this < static::wrap($now);
    }

    /**
     * Is less than or equal to.
     */
    public function isLte(\DateTimeInterface|ClockInterface|string|int $now = 'now'): bool
    {
        return $this <= static::wrap($now);
    }

    /**
     * Check date is now.
     *
     * You can provide a range as first argument. If you set as 30s, then the range is before 30s and after 30s,
     * total is 60s. The range string format please see {@see createInterval()}
     *
     * @param  string|int|DateInterval|null  $range  The current time range, if is NULL, will compare to second.
     *
     * @return  bool
     *
     * @throws  Exception
     */
    public function isCurrent(string|int|DateInterval|null $range = null): bool
    {
        if (is_string($range) || is_int($range)) {
            $range = static::createInterval($range);
        }

        if ($range === null) {
            return $this->getTimestamp() === time();
        }

        $now = time();
        $sec = static::intervalToSeconds($range);

        $start = $now - $sec;
        $end = $now + $sec;
        $self = $this->getTimestamp();

        return $self >= $start && $self <= $end;
    }

    /**
     * Magic method to render the date object in the format specified in the public
     * static member JDate::$format.
     *
     * @return  string  The date as a formatted string.
     *
     * @since   2.1
     */
    public function __toString(): string
    {
        return $this->format(static::$format);
    }

    /**
     * Get the time offset from GMT in hours or seconds.
     *
     * @param  boolean  $hours  True to return the value in hours.
     *
     * @return  float  The time offset from GMT either in hours or in seconds.
     *
     * @since   2.1
     */
    public function getOffsetFromGmt(bool $hours = false): float
    {
        $tz = $this->getTimezone();

        return (float) $hours ? ($tz->getOffset($this) / 3600) : $tz->getOffset($this);
    }

    /**
     * Method to wrap the setTimezone() function and set the internal time zone object.
     *
     * @param  DateTimeZone|string  $timezone  The new DateTimeZone object.
     *
     * @return  static
     *
     * @since   2.1
     */
    public function setTimezone(DateTimeZone|string $timezone): static
    {
        if (!$timezone instanceof DateTimeZone) {
            $timezone = new DateTimeZone($timezone);
        }

        return parent::setTimezone($timezone);
    }

    /**
     * Same instant, shown in another time zone. The wall-clock fields change.
     */
    public function withTimezone(DateTimeZone|string $timezone): static
    {
        return $this->setTimezone($timezone);
    }

    /**
     * Same wall-clock fields, placed in another time zone. The instant changes.
     * A wall-clock time that does not exist in the target zone (DST gap) is shifted forward by PHP.
     */
    public function withTimezoneKeepingLocal(DateTimeZone|string $timezone): static
    {
        return new static($this->format('Y-m-d H:i:s.u'), static::wrapTimezoneObject($timezone));
    }

    /**
     * Gets the date as an ISO 8601 string.  IETF RFC 3339 defines the ISO 8601 format
     * and it can be found at the IETF Web site.
     *
     * @return  string  The date string in ISO 8601 format.
     *
     * @link    http://www.ietf.org/rfc/rfc3339.txt
     * @since   2.1
     */
    public function toISO8601(bool $micro = false, string|DateTimeZone|null $tz = null): string
    {
        return $this->format($micro ? static::RFC3339_EXTENDED : static::RFC3339, $tz);
    }

    /**
     * Gets the date as an RFC 822 string.  IETF RFC 2822 supercedes RFC 822 and its definition
     * can be found at the IETF Web site.
     *
     * @return  string  The date string in RFC 822 format.
     *
     * @link    http://www.ietf.org/rfc/rfc2822.txt
     * @since   2.1
     */
    public function toRFC822(string|DateTimeZone|null $tz = null): string
    {
        return $this->format(static::RFC2822, $tz);
    }

    /**
     * Gets the date as UNIX time stamp.
     *
     * @param  bool  $micro
     *
     * @return  int|float  The date as a UNIX timestamp.
     *
     * @since   2.1
     */
    public function toUnix(bool $micro = false): int|float
    {
        if (!$micro) {
            return (int) $this->format('U');
        }

        return (float) $this->format('U.u');
    }

    public static function setClock(ClockInterface|\DateTimeInterface|string|int|float|null $clock): void
    {
        Clock::set($clock);
    }

    public static function fromClock(ClockInterface $clock, DateTimeZone|string|null $tz = null): static
    {
        return new static($clock, $tz);
    }

    public function jsonSerialize(): string
    {
        return $this->toISO8601(true);
    }

    public function getEpochSecond(): int
    {
        return $this->getTimestamp();
    }

    public function toChronos(DateTimeZone|string|null $tz = null): static
    {
        if ($tz === null) {
            return $this;
        }

        return $this->setTimezone(is_string($tz) ? new DateTimeZone($tz) : $tz);
    }

    public function toInstant(): Instant
    {
        return Instant::from($this);
    }

    public function toPlainDate(): PlainDate
    {
        return PlainDate::from($this);
    }

    public function toPlainTime(): PlainTime
    {
        return PlainTime::from($this);
    }

    public function toPlainDateTime(): PlainDateTime
    {
        return PlainDateTime::from($this);
    }

    /**
     * Compare the exact instants, the time zone is ignored.
     *
     * @throws  \TypeError  When $other has no instant, e.g. PlainDate.
     */
    public function compareTo(ChronosInterface|DateTimeInterface $other): int
    {
        $other = match (true) {
            $other instanceof DateTimeInterface => $other,
            $other instanceof HasInstantInterface => $other->toInstant()->toDateTime(),
            default => throw new \TypeError(
                sprintf('Cannot compare %s with %s.', static::class, get_debug_type($other))
            ),
        };

        return $this <=> $other;
    }

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

    /**
     * Calendar units (years, months, days) move the wall clock in the current time zone,
     * time units move the exact time. See {@see ChronosInterface} for $overflow.
     */
    public function plus(Duration|DateInterval|string $interval, DateOverflow $overflow = DateOverflow::Constrain): static
    {
        return static::createFromInterface(ChronosMath::addTo($this, ChronosMath::fields($interval), $overflow));
    }

    public function minus(Duration|DateInterval|string $interval, DateOverflow $overflow = DateOverflow::Constrain): static
    {
        return static::createFromInterface(
            ChronosMath::addTo($this, ChronosMath::negate(ChronosMath::fields($interval)), $overflow)
        );
    }

    public function toIso(): string
    {
        return $this->toISO8601($this->getMicrosecond() !== 0);
    }
}
