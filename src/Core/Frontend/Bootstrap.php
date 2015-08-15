<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Frontend;

/**
 * The Bootstrap class.
 *
 * @since  2.0.9
 */
class Bootstrap
{
	const MSG_SUCCESS = 'success';
	const MSG_INFO    = 'info';
	const MSG_WARNING = 'warning';
	const MSG_DANGER  = 'danger';

	const COLOR_DANGER  = 'danger';
	const COLOR_WARNING = 'warning';
	const COLOR_INFO    = 'info';
	const COLOR_PRIMARY = 'primary';
	const COLOR_DEFAULT = 'default';
	const COLOR_SUCCESS = 'success';

	const COM_TEXT     = 'text';
	const COM_BUTTON   = 'btn';
	const COM_LABEL    = 'label';
	const COM_PROGRESS = 'progress';
	const COM_ALERT    = 'alert';

	/**
	 * Property colorMapping.
	 *
	 * @var  array
	 */
	protected static $colorMapping = array(
		self::COLOR_DANGER => array(
			self::COM_TEXT     => 'text-danger',
			self::COM_BUTTON   => 'btn-danger',
			self::COM_LABEL    => 'label-danger',
			self::COM_PROGRESS => 'progress-danger',
			self::COM_ALERT    => 'alert-danger',
		),
		self::COLOR_WARNING => array(
			self::COM_TEXT     => 'text-warning',
			self::COM_BUTTON   => 'btn-warning',
			self::COM_LABEL    => 'label-warning',
			self::COM_PROGRESS => 'progress-warning',
			self::COM_ALERT    => 'alert-warning',
		),
		self::COLOR_INFO => array(
			self::COM_TEXT     => 'text-info',
			self::COM_BUTTON   => 'btn-info',
			self::COM_LABEL    => 'label-info',
			self::COM_PROGRESS => 'progress-info',
			self::COM_ALERT    => 'alert-info',
		),
		self::COLOR_PRIMARY => array(
			self::COM_TEXT     => 'text-primary',
			self::COM_BUTTON   => 'btn-primary',
			self::COM_LABEL    => 'label-primary',
			self::COM_PROGRESS => 'progress-primary',
			self::COM_ALERT    => 'alert-primary',
		),
		self::COLOR_DEFAULT => array(
			self::COM_TEXT     => 'text-default',
			self::COM_BUTTON   => 'btn-default',
			self::COM_LABEL    => 'label-default',
			self::COM_PROGRESS => 'progress-default',
			self::COM_ALERT    => 'alert-default',
		),
		self::COLOR_SUCCESS => array(
			self::COM_TEXT     => 'text-success',
			self::COM_BUTTON   => 'btn-success',
			self::COM_LABEL    => 'label-success',
			self::COM_PROGRESS => 'progress-success',
			self::COM_ALERT    => 'alert-success',
		),
	);

	/**
	 * getColorClass
	 *
	 * @param   string  $color
	 * @param   string  $component
	 *
	 * @return  string
	 */
	public static function getColorClass($color, $component)
	{
		if (isset(static::$colorMapping[$color][$component]))
		{
			return static::$colorMapping[$color][$component];
		}

		return null;
	}

	/**
	 * getColorClasses
	 *
	 * @param   string  $color
	 *
	 * @return  string
	 */
	public static function getColorClasses($color)
	{
		if (isset(static::$colorMapping[$color]))
		{
			return static::$colorMapping[$color];
		}

		return null;
	}
}
