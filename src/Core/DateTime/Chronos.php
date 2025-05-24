<?php

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use JsonSerializable;

/**
 * The Chronos class.
 *
 * @property-read string $daysinmonth
 * @property-read string $dayofweek
 * @property-read string $dayofyear
 * @property-read bool   $isleapyear
 * @property-read string $day
 * @property-read string $hour
 * @property-read string $minute
 * @property-read string $second
 * @property-read string $month
 * @property-read string $ordinal
 * @property-read string $week
 * @property-read string $year
 */
class Chronos extends DateTimeImmutable implements JsonSerializable
{
    public const FORMAT_YMD = 'Y-m-d';

    public const FORMAT_YMD_HI = 'Y-m-d H:i';

    public const FORMAT_YMD_HIS = 'Y-m-d H:i:s';

    /**
     * The format string to be applied when using the __toString() magic method.
     *
     * @var    string
     * @since  2.1
     */
    public static string $format = 'Y-m-d H:i:s';

    /**
     * @param  mixed                     $date
     * @param  string|DateTimeZone|null  $tz
     *
     * @return  static
     *
     * @throws Exception
     * @since  3.5.19
     */
    public static function wrap(mixed $date = 'now', string|DateTimeZone|null $tz = null): static
    {
        if ($date instanceof static) {
            return $date;
        }

        if ($date instanceof DateTimeInterface) {
            return static::createFromFormat('U.u', $date->format('U.u'), $date->getTimezone());
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
    public static function wrapOrNull(mixed $date = 'now', string|DateTimeZone|null $tz = null): ?static
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
     * @throws Exception
     * @since   2.1
     */
    public function __construct(mixed $date = 'now', string|DateTimeZone|null $tz = null)
    {
        $tz = static::wrapTimezoneObject($tz);

        // If the date is numeric assume a unix timestamp and convert it.
        $date = is_numeric($date) ? date('c', (int) $date) : $date;

        if ($date instanceof DateTimeInterface) {
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
     * @return string
     *
     * @throws Exception
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
     * @throws Exception
     */
    public static function now(string $format = self::FORMAT_YMD_HIS, string|DateTimeZone|null $tz = null): string
    {
        return (new static('now', $tz))->format($format, $tz);
    }

    /**
     * Proxy for new DateTime.
     *
     * @param  string                    $date  String in a format accepted by strtotime(), defaults to "now".
     * @param  string|DateTimeZone|null  $tz    Time zone to be used for the date.
     *
     * @return  static
     *
     * @throws Exception
     * @since   2.1
     */
    public static function create(mixed $date = 'now', string|DateTimeZone|null $tz = null): static
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
     * @return static|bool
     * @throws Exception
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
     * @throws Exception
     */
    public static function createInterval(string|int $duration): DateInterval
    {
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
     * @param  DateInterval|string  $diff
     *
     * @return  int
     * @throws Exception
     */
    public static function intervalToSeconds(DateInterval|string $diff): int
    {
        if (!$diff instanceof DateInterval) {
            $diff = new DateInterval($diff);
        }

        return (int) ($diff->format('%r') . (
                ($diff->s) +
                (60 * ($diff->i)) +
                (60 * 60 * ($diff->h)) +
                (24 * 60 * 60 * ($diff->d)) +
                (30 * 24 * 60 * 60 * ($diff->m)) +
                (365 * 24 * 60 * 60 * ($diff->y))
            ));
    }

    /**
     * @param  string|int|DateTimeInterface  $start
     * @param  string|int|DateInterval       $interval
     * @param  string|int|DateTimeInterface  $end
     * @param  int                           $options
     *
     * @return  \DatePeriod
     *
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
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

    public function isFuture(): bool
    {
        return $this->getTimestamp() > time();
    }

    public function isPast(): bool
    {
        return $this->getTimestamp() < time();
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
     * @throws Exception
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
     * Magic method to access properties of the date given by class to the format method.
     *
     * @param  string  $name  The name of the property.
     *
     * @return  mixed   A value if the property name is valid, null otherwise.
     *
     * @since   2.1
     */
    public function __get(string $name)
    {
        switch ($name) {
            case 'daysinmonth':
                return $this->format('t');

            case 'dayofweek':
                return $this->format('N');

            case 'dayofyear':
                return $this->format('z');

            case 'isleapyear':
                return (bool) $this->format('L');

            case 'day':
                return $this->format('d');

            case 'hour':
                return $this->format('H');

            case 'minute':
                return $this->format('i');

            case 'second':
                return $this->format('s');

            case 'month':
                return $this->format('m');

            case 'ordinal':
                return $this->format('S');

            case 'week':
                return $this->format('W');

            case 'year':
                return $this->format('Y');
        }

        throw new InvalidArgumentException('Property ' . $name . ' not exists.');
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
     * @return  string   The date string in RFC 822 format.
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

    public function jsonSerialize(): string
    {
        return $this->toISO8601(true);
    }
}
