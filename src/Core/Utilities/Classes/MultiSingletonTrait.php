<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT Taiwan, Inc. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Utilities\Classes;

/**
 * The MultiSingletonTrait class.
 *
 * @since  3.0
 */
trait MultiSingletonTrait
{
	/**
	 * Property instances.
	 *
	 * @var  array
	 */
	protected static $instances = [];

	/**
	 * getInstance
	 *
	 * @param string $name
	 * @param array  ...$args
	 *
	 * @return static
	 */
	public static function getInstance($name, ...$args)
	{
		if (empty(static::$instances[$name]))
		{
			static::$instances[$name] = new static(...$args);
		}

		return static::$instances[$name];
	}

	/**
	 * setInstance
	 *
	 * @param string $name
	 * @param object $instance
	 *
	 * @return  mixed
	 */
	protected static function setInstance($name, $instance)
	{
		return static::$instances[$name] = $instance;
	}

	/**
	 * hasInstance
	 *
	 * @param string $name
	 *
	 * @return  bool
	 */
	protected static function hasInstance($name)
	{
		return isset(static::$instances[$name]);
	}
}