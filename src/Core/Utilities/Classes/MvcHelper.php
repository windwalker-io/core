<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Utilities\Classes;

/**
 * The MvcHelper class.
 * 
 * @since  {DEPLOY_VERSION}
 */
abstract class MvcHelper
{
	/**
	 * guessName
	 *
	 * @param string|object $class
	 * @param int           $backwards
	 * @param string        $default
	 *
	 * @return  string
	 */
	public static function guessName($class, $backwards = 2, $default = 'default')
	{
		if (!is_string($class))
		{
			$class = get_class($class);
		}

		$class = explode('\\', $class);

		$name = null;

		foreach (range(1, $backwards) as $i)
		{
			$name = array_pop($class);
		}

		$name = $name ? : $default;

		return strtolower($name);
	}

	/**
	 * guessPackage
	 *
	 * @param string|object $class
	 * @param int           $backwards
	 * @param string        $default
	 *
	 * @return  string
	 */
	public static function guessPackage($class, $backwards = 4, $default = null)
	{
		return static::guessName($class, $backwards, $default);
	}

	/**
	 * getPackageNamespace
	 *
	 * @param string|object $class
	 * @param int           $backwards
	 *
	 * @return  string
	 */
	public static function getPackageNamespace($class, $backwards = 3)
	{
		if (!is_string($class))
		{
			$class = get_class($class);
		}

		$class = explode('\\', $class);

		foreach (range(1, $backwards) as $i)
		{
			array_pop($class);
		}

		return implode('\\', $class);
	}
}
