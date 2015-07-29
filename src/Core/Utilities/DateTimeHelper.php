<?php
/**
 * Part of Windwalker project. 
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Utilities;

/**
 * The DateTimeHelper class.
 * 
 * @since  2.0
 */
abstract class DateTimeHelper
{
	/**
	 * Placeholder for a DateTimeZone object with GMT as the time zone.
	 *
	 * @var    object
	 * @since  11.1
	 */
	protected static $gmt;

	/**
	 * Placeholder for a DateTimeZone object with the default server
	 * time zone as the time zone.
	 *
	 * @var    object
	 * @since  11.1
	 */
	protected static $stz;

	/**
	 * setDefaultGMT
	 *
	 * @return  void
	 */
	public static function setDefaultTimezone($tz = 'UTC')
	{
		static::getTimezoneBackup();

		date_default_timezone_set($tz);
	}

	/**
	 * resetDefaultTimezone
	 *
	 * @return  void
	 */
	public static function resetDefaultTimezone()
	{
		static::getTimezoneBackup();

		date_default_timezone_set(static::$stz->getName());
	}

	/**
	 * getTimezoneBackup
	 *
	 * @return  void
	 */
	protected static function getTimezoneBackup()
	{
		// Create the base GMT and server time zone objects.
		if (empty(static::$gmt) || empty(static::$stz))
		{
			static::$gmt = new \DateTimeZone('GMT');
			static::$stz = new \DateTimeZone(ini_get('date.timezone') ? : 'UTC');
		}
	}
}
