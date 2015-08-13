<?php
/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2015 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Facade;

use Windwalker\DI\Container;
use Windwalker\Core\Ioc;

/**
 * The AbstractFacade class.
 *
 * @since  {DEPLOY_VERSION}
 */
abstract class AbstractFacade
{
	/**
	 * Property key.
	 *
	 * @var  string
	 */
	protected static $_key;

	/**
	 * Property child.
	 *
	 * @var  string
	 */
	protected static $_child;

	/**
	 * Property profile.
	 *
	 * @var  string
	 */
	protected static $_profile;

	/**
	 * getInstance
	 *
	 * @param bool $forceNew
	 *
	 * @return mixed
	 */
	public static function getInstance($forceNew = false)
	{
		return static::getContainer(static::getContainerName(), static::getIocProfile())
			->get(static::getDIKey(), $forceNew);
	}

	/**
	 * Method to get property Container
	 *
	 * @param  string $child
	 * @param  string $profile
	 *
	 * @return Container
	 */
	public static function getContainer($child = null, $profile = null)
	{
		return Ioc::factory($child ? : static::getContainerName(), $profile ? : static::getIocProfile());
	}

	/**
	 * Method to get property Child
	 *
	 * @return  string
	 */
	public static function getContainerName()
	{
		return static::$_child;
	}

	/**
	 * setContainerName
	 *
	 * @param   string  $name
	 *
	 * @return  void
	 */
	public static function setContainerName($name)
	{
		static::$_child = $name;
	}

	/**
	 * Get Ioc profile.
	 *
	 * @return  string
	 */
	public static function getIocProfile()
	{
		return static::$_profile;
	}

	/**
	 * setIocProfile
	 *
	 * @param   string  $profile
	 *
	 * @return  void
	 */
	public static function setIocProfile($profile)
	{
		static::$_profile = $profile;
	}

	/**
	 * getDIKey
	 *
	 * @return  string
	 */
	public static function getDIKey()
	{
		return static::$_key;
	}

	/**
	 * setDIKey
	 *
	 * @param   string  $key
	 *
	 * @return  void
	 */
	public static function setDIKey($key)
	{
		static::$_key = $key;
	}
}
