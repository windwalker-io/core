<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\DateTime;

use Windwalker\Database\Driver\AbstractDatabaseDriver;
use Windwalker\Ioc;

/**
 * The DateTimeTrait class.
 *
 * @property-read  string daysinmonth
 * @property-read  string dayofweek
 * @property-read  string dayofyear
 * @property-read  string isleapyear
 * @property-read  string day
 * @property-read  string hour
 * @property-read  string minute
 * @property-read  string second
 * @property-read  string month
 * @property-read  string ordinal
 * @property-read  string week
 * @property-read  string year
 *
 * @since  3.5
 */
trait DateTimeTrait
{
    /**
     * The format string to be applied when using the __toString() magic method.
     *
     * @var    string
     * @since  2.1
     */
    public static $format = 'Y-m-d H:i:s';

    /**
     * Placeholder for a DateTimeZone object with GMT as the time zone.
     *
     * @var  \DateTimeZone
     */
    protected static $gmt;

    /**
     * Placeholder for a DateTimeZone object with the default server
     * time zone as the time zone.
     *
     * @var  \DateTimeZone
     */
    protected static $stz;

    /**
     * The DateTimeZone object for usage in rending dates as strings.
     *
     * @var    \DateTimeZone
     * @since  2.1
     */
    protected $tz;

    /**
     * Property useServerDefaultTimezone.
     *
     * @var  boolean
     */
    protected static $useStz = true;

    /**
     * wrap
     *
     * @param mixed $date
     * @param mixed $tz
     *
     * @return  static
     *
     * @throws \Exception
     * @since  3.5.19
     */
    public static function wrap($date = 'now', $tz = null)
    {
        if ($date instanceof static) {
            return $date;
        }

        if ($date instanceof \DateTimeInterface) {
            return static::createFromFormat('U.u', $date->format('U.u'), $date->getTimezone());
        }

        return static::create($date, $tz);
    }

    /**
     * Constructor.
     *
     * @param   string $date String in a format accepted by strtotime(), defaults to "now".
     * @param   mixed  $tz   Time zone to be used for the date. Might be a string or a DateTimeZone object.
     *
     * @since   2.1
     * @throws \Exception
     */
    public function __construct($date = 'now', $tz = null)
    {
        self::backupTimezone();

        $tz = self::getTimezoneObject($tz);

        // If the date is numeric assume a unix timestamp and convert it.
        $date = is_numeric($date) ? date('c', $date) : $date;

        // Call the DateTime constructor.
        parent::__construct($date, $tz);

        // Set the timezone object for access later.
        $this->tz = $tz;
    }

    /**
     * setDefaultGMT
     *
     * @param string $tz
     *
     * @return  void
     */
    public static function setDefaultTimezone($tz = 'UTC')
    {
        self::backupTimezone();

        date_default_timezone_set($tz);
    }

    /**
     * resetDefaultTimezone
     *
     * @return  void
     */
    public static function resetDefaultTimezone()
    {
        self::backupTimezone();

        date_default_timezone_set(self::$stz->getName());
    }

    /**
     * getTimezoneBackup
     *
     * @return  void
     */
    protected static function backupTimezone()
    {
        // Create the base GMT and server time zone objects.
        if (empty(self::$gmt) || empty(self::$stz)) {
            self::$gmt = new \DateTimeZone('GMT');
            self::$stz = new \DateTimeZone(date_default_timezone_get() ?: 'UTC');
        }
    }

    /**
     * Convert a date string to another timezone.
     *
     * @param string $date
     * @param string $from
     * @param string $to
     * @param string $format
     *
     * @return  string
     * @throws \Exception
     */
    public static function convert($date, $from = 'UTC', $to = 'UTC', $format = null)
    {
        $format = $format ?: self::$format;

        $from = new \DateTimeZone($from);
        $to   = new \DateTimeZone($to);
        $date = new static($date, $from);

        $date = $date->setTimezone($to);

        return $date->format($format, true);
    }

    /**
     * utcToLocal
     *
     * @param string $date
     * @param string $format
     * @param string $to
     *
     * @return  string
     * @throws \Exception
     */
    public static function toLocalTime($date, $format = null, $to = null)
    {
        $to = $to ?: Ioc::getConfig()->get('system.timezone');

        return static::convert($date, self::getServerDefaultTimezone(), $to, $format);
    }

    /**
     * localToUTC
     *
     * @param string $date
     * @param string $format
     * @param string $from
     *
     * @return  string
     * @throws \Exception
     */
    public static function toServerTime($date, $format = null, $from = null)
    {
        $from = $from ?: Ioc::getConfig()->get('system.timezone');

        return static::convert($date, $from, self::getServerDefaultTimezone(), $format);
    }

    /**
     * toFormat
     *
     * @param string|\DateTimeInterface $date
     * @param string                    $format
     * @param bool                      $local
     *
     * @return string
     *
     * @since  3.2
     * @throws \Exception
     */
    public static function toFormat($date, $format, $local = false)
    {
        if (is_string($date)) {
            $date = new static($date);
        }

        return $date->format($format, $local);
    }

    /**
     * compare
     *
     * @param string|\DateTimeInterface $date1
     * @param string|\DateTimeInterface $date2
     * @param string                    $operator
     *
     * @return  bool|int
     * @throws \Exception
     */
    public static function compare($date1, $date2, $operator = null)
    {
        $date1 = $date1 instanceof \DateTimeInterface ? $date1 : new \DateTime($date1);
        $date2 = $date2 instanceof \DateTimeInterface ? $date2 : new \DateTime($date2);

        if ($operator === null) {
            if ($date1 == $date2) {
                return 0;
            }

            return (int) ($date1 < $date2);
        }

        switch ((string) $operator) {
            case '=':
                return $date1 == $date2;
            case '!=':
                return $date1 != $date2;
            case '>':
            case 'gt':
                return $date1 > $date2;
            case '>=':
            case 'gte':
                return $date1 >= $date2;
            case '<':
            case 'lt':
                return $date1 < $date2;
            case '<=':
            case 'lte':
                return $date1 <= $date2;
        }

        throw new \InvalidArgumentException('Invalid operator: ' . $operator);
    }

    /**
     * compare
     *
     * @param string|\DateTimeInterface $date1
     * @param string|\DateTimeInterface $date2
     * @param string                    $operator
     *
     * @return  bool|int
     * @throws \Exception
     */
    public static function compareWithTz($date1, $date2, $operator = null)
    {
        $date1 = $date1 instanceof \DateTimeInterface ? $date1 : new static($date1, true);
        $date2 = $date2 instanceof \DateTimeInterface ? $date2 : new static($date2, true);

        if ($operator === null) {
            if ($date1 == $date2) {
                return 0;
            }

            return (int) ($date1 < $date2);
        }

        switch ((string) $operator) {
            case '=':
                return $date1 == $date2;
            case '!=':
                return $date1 != $date2;
            case '>':
            case 'gt':
                return $date1 > $date2;
            case '>=':
            case 'gte':
                return $date1 >= $date2;
            case '<':
            case 'lt':
                return $date1 < $date2;
            case '<=':
            case 'lte':
                return $date1 <= $date2;
        }

        throw new \InvalidArgumentException('Invalid operator: ' . $operator);
    }

    /**
     * current
     *
     * @param string $format
     * @param bool   $local
     *
     * @return  string
     * @throws \Exception
     */
    public static function current($format = self::FORMAT_YMD_HIS, $local = false)
    {
        return (new static('now', $local ? true : null))->format($format, $local);
    }

    /**
     * Proxy for new DateTime.
     *
     * @param   string $date String in a format accepted by strtotime(), defaults to "now".
     * @param   mixed  $tz   Time zone to be used for the date.
     *
     * @return  static
     *
     * @since   2.1
     * @throws \Exception
     */
    public static function create($date = 'now', $tz = null)
    {
        return new static($date, $tz);
    }

    /**
     * Parse a string into a new DateTime object according to the specified format
     *
     * @param string $format   Format accepted by date().
     * @param string $time     String representing the time.
     * @param mixed  $timezone A DateTimeZone object representing the desired time zone.
     *
     * @return static|bool
     * @throws \Exception
     */
    #[\ReturnTypeWillChange]
    public static function createFromFormat($format, $time, $timezone = null)
    {
        $datetime = parent::createFromFormat($format, $time, self::getTimezoneObject($timezone));

        if (!$datetime) {
            return false;
        }

        $chronos = new static($datetime->getTimestamp(), $datetime->getTimezone());
        $chronos = $chronos->setTime(
            $datetime->format('H'),
            $datetime->format('i'),
            $datetime->format('s'),
            $datetime->format('u')
        );

        return $chronos;
    }

    /**
     * Method to set property useServerDefaultTimezone
     *
     * @param   boolean $boolean
     *
     * @return  boolean
     */
    public static function useServerDefaultTimezone($boolean = null)
    {
        if ($boolean !== null) {
            self::$useStz = (bool) $boolean;

            return self::$useStz;
        }

        return self::$useStz;
    }

    /**
     * getServerDefaultTimezone
     *
     * @return  string
     *
     * @since  3.5.7
     */
    public static function getServerDefaultTimezone(): string
    {
        return date_default_timezone_get();
    }

    /**
     * Magic method to access properties of the date given by class to the format method.
     *
     * @param   string $name The name of the property.
     *
     * @return  mixed   A value if the property name is valid, null otherwise.
     *
     * @since   2.1
     */
    public function __get($name)
    {
        switch ($name) {
            case 'daysinmonth':
                return $this->format('t', true);

            case 'dayofweek':
                return $this->format('N', true);

            case 'dayofyear':
                return $this->format('z', true);

            case 'isleapyear':
                return (bool) $this->format('L', true);

            case 'day':
                return $this->format('d', true);

            case 'hour':
                return $this->format('H', true);

            case 'minute':
                return $this->format('i', true);

            case 'second':
                return $this->format('s', true);

            case 'month':
                return $this->format('m', true);

            case 'ordinal':
                return $this->format('S', true);

            case 'week':
                return $this->format('W', true);

            case 'year':
                return $this->format('Y', true);
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
    public function __toString()
    {
        return (string) parent::format(static::$format);
    }

    /**
     * Gets the date as a formatted string.
     *
     * @param   string  $format The date format specification string (see {@link PHP_MANUAL#date})
     * @param   boolean $local  True to return the date string in the local time zone, false to return it in GMT.
     *
     * @return  string   The date string in the specified format format.
     *
     * @since   2.1
     */
    #[\ReturnTypeWillChange]
    public function format($format, $local = false)
    {
        // If the returned time should not be local use GMT.
        if ($local === false) {
            parent::setTimezone(self::$stz);
        }

        // Format the date.
        $return = parent::format($format);

        if ($local === false && $this->tz) {
            parent::setTimezone($this->tz);
        }

        return $return;
    }

    /**
     * Get the time offset from GMT in hours or seconds.
     *
     * @param   boolean $hours True to return the value in hours.
     *
     * @return  float  The time offset from GMT either in hours or in seconds.
     *
     * @since   2.1
     */
    public function getOffsetFromGmt($hours = false)
    {
        return (float) $hours ? ($this->tz->getOffset($this) / 3600) : $this->tz->getOffset($this);
    }

    /**
     * Method to wrap the setTimezone() function and set the internal time zone object.
     *
     * @param   \DateTimeZone|string $tz The new DateTimeZone object.
     *
     * @return  static
     *
     * @since   2.1
     */
    #[\ReturnTypeWillChange]
    public function setTimezone($tz)
    {
        if (!$tz instanceof \DateTimeZone) {
            $tz = new \DateTimeZone($tz);
        }

        $this->tz = $tz;

        return parent::setTimezone($tz);
    }

    /**
     * Gets the date as an ISO 8601 string.  IETF RFC 3339 defines the ISO 8601 format
     * and it can be found at the IETF Web site.
     *
     * @param   boolean $local True to return the date string in the local time zone, false to return it in GMT.
     *
     * @return  string  The date string in ISO 8601 format.
     *
     * @link    http://www.ietf.org/rfc/rfc3339.txt
     * @since   2.1
     */
    public function toISO8601($local = false)
    {
        return $this->format(Chronos::RFC3339, $local);
    }

    /**
     * Gets the date as an SQL datetime string.
     *
     * @param   boolean                $local True to return the date string in the local time zone, false to return it
     *                                        in GMT.
     * @param   AbstractDatabaseDriver $db    The database driver or null to use JFactory::getDbo()
     *
     * @return  string     The date string in SQL datetime format.
     *
     * @link    http://dev.mysql.com/doc/refman/5.0/en/datetime.html
     * @since   11.4
     */
    public function toSql($local = false, AbstractDatabaseDriver $db = null)
    {
        return $this->format(static::getSqlFormat($db), $local);
    }

    /**
     * Gets the date as an RFC 822 string.  IETF RFC 2822 supercedes RFC 822 and its definition
     * can be found at the IETF Web site.
     *
     * @param   boolean $local True to return the date string in the local time zone, false to return it in GMT.
     *
     * @return  string   The date string in RFC 822 format.
     *
     * @link    http://www.ietf.org/rfc/rfc2822.txt
     * @since   2.1
     */
    public function toRFC822($local = false)
    {
        return $this->format(Chronos::RFC2822, $local);
    }

    /**
     * Gets the date as UNIX time stamp.
     *
     * @return  integer  The date as a UNIX timestamp.
     *
     * @since   2.1
     */
    public function toUnix()
    {
        return (int) parent::format('U');
    }

    /**
     * getSqlFormat
     *
     * @param AbstractDatabaseDriver $db
     *
     * @return  string
     */
    public static function getSqlFormat(AbstractDatabaseDriver $db = null)
    {
        $db = $db ?: Ioc::getDatabase();

        return $db->getQuery(true)->getDateFormat();
    }

    /**
     * Get NULL date.
     *
     * @param AbstractDatabaseDriver $db
     *
     * @return  string
     *
     * @since   3.2.6.1
     */
    public static function getNullDate(AbstractDatabaseDriver $db = null)
    {
        $db = $db ?: Ioc::getDatabase();

        return $db->getQuery(true)->getNullDate();
    }

    /**
     * isNullDate
     *
     * @param string|null $dateString
     * @param bool        $includeEmpty
     *
     * @return  bool
     *
     * @since  3.5.8
     */
    public static function isNullDate(?string $dateString, bool $includeEmpty = false): bool
    {
        $nullDates = [
            static::getNullDate(),
            '0000-00-00 00:00:00'
        ];

        if ($includeEmpty) {
            $nullDates = array_merge($nullDates, [
                ''
            ]);
        }

        return in_array((string) $dateString, $nullDates, true);
    }

    /**
     * getTimezoneObject
     *
     * @param mixed $tz
     *
     * @return  \DateTimeZone
     */
    protected static function getTimezoneObject($tz)
    {
        // If the time zone object is not set, attempt to build it.
        if (!($tz instanceof \DateTimeZone)) {
            if ($tz === null) {
                $tz = static::useServerDefaultTimezone() ? self::$stz : self::$gmt;
            } elseif (is_string($tz)) {
                $tz = new \DateTimeZone($tz);
            } elseif ($tz === static::TZ_LOCALE) {
                $config = Ioc::getConfig();
                $tz     = $config->get('system.timezone');
                $tz     = new \DateTimeZone($tz);
            }
        }

        return $tz;
    }
}
