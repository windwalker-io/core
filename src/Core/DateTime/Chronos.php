<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\DateTime;

/**
 * The Chronos class.
 */
class Chronos extends \DateTimeImmutable implements \JsonSerializable
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
     * wrap
     *
     * @param  mixed  $date
     * @param  mixed  $tz
     *
     * @return  static
     *
     * @throws \Exception
     * @since  3.5.19
     */
    public static function wrap(mixed $date = 'now', string|\DateTimeZone $tz = null): static
    {
        if ($date instanceof static) {
            return $date;
        }

        if ($date instanceof \DateTimeInterface) {
            return static::createFromFormat('U.u', $date->format('U.u'), $date->getTimezone());
        }

        return static::create($date, $tz);
    }

    public static function wrapOrNull(mixed $date = 'now', string|\DateTimeZone $tz = null): ?static
    {
        if ($date === null) {
            return null;
        }

        return static::wrap($date, $tz);
    }

    /**
     * Constructor.
     *
     * @param  string  $date  String in a format accepted by strtotime(), defaults to "now".
     * @param  mixed   $tz    Time zone to be used for the date. Might be a string or a DateTimeZone object.
     *
     * @throws \Exception
     * @since   2.1
     */
    public function __construct(mixed $date = 'now', string|\DateTimeZone $tz = null)
    {
        $tz = static::wrapTimezoneObject($tz);

        // If the date is numeric assume a unix timestamp and convert it.
        $date = is_numeric($date) ? date('c', $date) : $date;

        if ($date instanceof \DateTimeInterface) {
            $date = $date->format('Y-m-d H:i:s.u');
        }

        // Call the DateTime constructor.
        parent::__construct($date, $tz);
    }

    public static function wrapTimezoneObject(string|\DateTimeZone $tz = null): ?\DateTimeZone
    {
        if (is_string($tz)) {
            $tz = new \DateTimeZone($tz);
        }

        return $tz;
    }

    /**
     * toFormat
     *
     * @param  string|\DateTimeInterface  $date
     * @param  string                     $format
     *
     * @return string
     *
     * @throws \Exception
     * @since  3.2
     */
    public static function toFormat(string|\DateTimeInterface $date, string $format): string
    {
        if (is_string($date)) {
            $date = new static($date);
        }

        return $date->format($format);
    }

    /**
     * current
     *
     * @param  string                     $format
     * @param  string|\DateTimeZone|null  $tz
     *
     * @return  string
     * @throws \Exception
     */
    public static function now(string $format = self::FORMAT_YMD_HIS, string|\DateTimeZone $tz = null): string
    {
        return (new static('now', $tz))->format($format, $tz);
    }

    /**
     * Proxy for new DateTime.
     *
     * @param  string  $date  String in a format accepted by strtotime(), defaults to "now".
     * @param  mixed   $tz    Time zone to be used for the date.
     *
     * @return  static
     *
     * @throws \Exception
     * @since   2.1
     */
    public static function create(string $date = 'now', string|\DateTimeZone $tz = null): static
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
     * @throws \Exception
     */
    public static function createFromFormat($format, $time, $timezone = null)
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
     * @inheritDoc
     */
    public function format($format, string|\DateTimeZone $tz = null): string
    {
        if (!$tz) {
            return parent::format($format);
        }

        $bakTz = $this->getTimezone();

        $this->setTimezone($tz);

        $formatted = parent::format($format);

        $this->setTimezone($bakTz);

        return $formatted;
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

        throw new \InvalidArgumentException('Property ' . $name . ' not exists.');
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
     * @param  \DateTimeZone|string  $timezone  The new DateTimeZone object.
     *
     * @return  static
     *
     * @since   2.1
     */
    public function setTimezone(\DateTimeZone|string $timezone): static
    {
        if (!$timezone instanceof \DateTimeZone) {
            $timezone = new \DateTimeZone($timezone);
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
    public function toISO8601(): string
    {
        return $this->format(static::RFC3339);
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
    public function toRFC822(): string
    {
        return $this->format(static::RFC2822);
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
        return $this->format('Y-m-d\TH:i:s');
    }
}
