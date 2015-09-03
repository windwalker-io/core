<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\DateTime;

use Windwalker\Core\Ioc;
use Windwalker\Database\Driver\AbstractDatabaseDriver;

/**
 * The DateTime class.
 *
 * Port of Joomla JDate and will be re-write after 3.0.
 *
 * @since  2.1.1
 */
class DateTime extends \DateTime
{
	const FORMAT_YMD     = 'Y-m-d';
	const FORMAT_YMD_HI  = 'Y-m-d H:i';
	const FORMAT_YMD_HIS = 'Y-m-d H:i:s';
	const FORMAT_SQL     = 'Y-m-d H:i:s';

	const TZ_LOCALE = true;

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
	protected static $useStz = false;

	/**
	 * Constructor.
	 *
	 * @param   string  $date  String in a format accepted by strtotime(), defaults to "now".
	 * @param   mixed   $tz    Time zone to be used for the date. Might be a string or a DateTimeZone object.
	 *
	 * @since   2.1
	 */
	public function __construct($date = 'now', $tz = null)
	{
		static::backupTimezone();

		// If the time zone object is not set, attempt to build it.
		if (!($tz instanceof \DateTimeZone))
		{
			if ($tz === null)
			{
				$tz = static::useServerDefaultTimezone() ? self::$stz : self::$gmt;
			}
			elseif (is_string($tz))
			{
				$tz = new \DateTimeZone($tz);
			}
			elseif ($tz === static::TZ_LOCALE)
			{
				$config = Ioc::getConfig();
				$tz = $config->get('system.timezone');
				$tz = new \DateTimeZone($tz);
			}
		}

		// If the date is numeric assume a unix timestamp and convert it.
		date_default_timezone_set('UTC');
		$date = is_numeric($date) ? date('c', $date) : $date;

		// Call the DateTime constructor.
		parent::__construct($date, $tz);

		// Reset the timezone for 3rd party libraries
		date_default_timezone_set(self::$stz->getName());

		// Set the timezone object for access later.
		$this->tz = $tz;
	}

	/**
	 * setDefaultGMT
	 *
	 * @return  void
	 */
	public static function setDefaultTimezone($tz = 'UTC')
	{
		static::backupTimezone();

		date_default_timezone_set($tz);
	}

	/**
	 * resetDefaultTimezone
	 *
	 * @return  void
	 */
	public static function resetDefaultTimezone()
	{
		static::backupTimezone();

		date_default_timezone_set(static::$stz->getName());
	}

	/**
	 * getTimezoneBackup
	 *
	 * @return  void
	 */
	protected static function backupTimezone()
	{
		// Create the base GMT and server time zone objects.
		if (empty(static::$gmt) || empty(static::$stz))
		{
			static::$gmt = new \DateTimeZone('GMT');
			static::$stz = new \DateTimeZone(ini_get('date.timezone') ? : 'UTC');
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
	 */
	public static function convert($date, $from = 'UTC', $to = 'UTC', $format = null)
	{
		$format = $format ? : static::$format;

		$from = new \DateTimeZone($from);
		$to   = new \DateTimeZone($to);
		$date = new static($date, $from);

		$date->setTimezone($to);

		return $date->format($format);
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
		if ($boolean !== null)
		{
			static::$useStz = (bool) $boolean;

			return static::$useStz;
		}

		return static::$useStz;
	}

	/**
	 * Magic method to access properties of the date given by class to the format method.
	 *
	 * @param   string  $name  The name of the property.
	 *
	 * @return  mixed   A value if the property name is valid, null otherwise.
	 *
	 * @since   2.1
	 */
	public function __get($name)
	{
		$value = null;

		switch ($name)
		{
			case 'daysinmonth':
				return $this->format('t', true);

			case 'dayofweek':
				return $this->format('N', true);

			case 'dayofyear':
				return $this->format('z', true);

			case 'isleapyear':
				return (boolean) $this->format('L', true);

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
	 * Proxy for new DateTime.
	 *
	 * @param   string  $date  String in a format accepted by strtotime(), defaults to "now".
	 * @param   mixed   $tz    Time zone to be used for the date.
	 *
	 * @return  static
	 *
	 * @since   2.1
	 */
	public static function create($date = 'now', $tz = null)
	{
		return new static($date, $tz);
	}

	/**
	 * Gets the date as a formatted string.
	 *
	 * @param   string   $format     The date format specification string (see {@link PHP_MANUAL#date})
	 * @param   boolean  $local      True to return the date string in the local time zone, false to return it in GMT.
	 *
	 * @return  string   The date string in the specified format format.
	 *
	 * @since   2.1
	 */
	public function format($format, $local = false)
	{
		// If the returned time should not be local use GMT.
		if ($local == false)
		{
			parent::setTimezone(self::$gmt);
		}

		// Format the date.
		$return = parent::format($format);

		if ($local == false)
		{
			parent::setTimezone($this->tz);
		}

		return $return;
	}

	/**
	 * Get the time offset from GMT in hours or seconds.
	 *
	 * @param   boolean  $hours  True to return the value in hours.
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
	 * @param   \DateTimeZone|string  $tz  The new DateTimeZone object.
	 *
	 * @return  static
	 *
	 * @since   2.1
	 */
	public function setTimezone($tz)
	{
		if (!$tz instanceof \DateTimeZone)
		{
			$tz = new \DateTimeZone($tz);
		}

		$this->tz = $tz;

		return parent::setTimezone($tz);
	}

	/**
	 * Gets the date as an ISO 8601 string.  IETF RFC 3339 defines the ISO 8601 format
	 * and it can be found at the IETF Web site.
	 *
	 * @param   boolean  $local  True to return the date string in the local time zone, false to return it in GMT.
	 *
	 * @return  string  The date string in ISO 8601 format.
	 *
	 * @link    http://www.ietf.org/rfc/rfc3339.txt
	 * @since   2.1
	 */
	public function toISO8601($local = false)
	{
		return $this->format(DateTime::RFC3339, $local);
	}

	/**
	 * Gets the date as an SQL datetime string.
	 *
	 * @param   boolean                 $local  True to return the date string in the local time zone, false to return it in GMT.
	 * @param   AbstractDatabaseDriver  $db     The database driver or null to use JFactory::getDbo()
	 *
	 * @return  string     The date string in SQL datetime format.
	 *
	 * @link    http://dev.mysql.com/doc/refman/5.0/en/datetime.html
	 * @since   11.4
	 */
	public function toSql($local = false, AbstractDatabaseDriver $db = null)
	{
		if ($db === null)
		{
			$db = Ioc::getDatabase();
		}

		return $this->format($db->getQuery(true)->getDateFormat(), $local);
	}

	/**
	 * Gets the date as an RFC 822 string.  IETF RFC 2822 supercedes RFC 822 and its definition
	 * can be found at the IETF Web site.
	 *
	 * @param   boolean  $local  True to return the date string in the local time zone, false to return it in GMT.
	 *
	 * @return  string   The date string in RFC 822 format.
	 *
	 * @link    http://www.ietf.org/rfc/rfc2822.txt
	 * @since   2.1
	 */
	public function toRFC822($local = false)
	{
		return $this->format(DateTime::RFC2822, $local);
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
}
