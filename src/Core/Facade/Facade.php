<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Facade;

use Windwalker\Core\Ioc;
use Windwalker\DI\Container;

/**
 * The AbstractFacade class.
 * 
 * @since  2.0
 */
abstract class Facade
{
	/**
	 * Property key.
	 *
	 * @var  string
	 */
	protected static $key;

	/**
	 * Property group.
	 *
	 * @var string
	 */
	protected static $name;

	/**
	 * Property container.
	 *
	 * @var Container
	 */
	protected static $container;

	/**
	 * Property instance.
	 *
	 * @var mixed
	 */
	protected static $instance;

	/**
	 * getInstance
	 *
	 * @return  mixed|object
	 */
	protected static function getInstance()
	{
		if (!static::$key)
		{
			throw new \LogicException('Key not set');
		}

		if (empty(static::$instance[static::$key]))
		{
			static::$instance[static::$key] = static::getContainer()->get(static::$key);
		}

		return static::$instance[static::$key];
	}

	/**
	 * __callStatic
	 *
	 * @param string $name
	 * @param array  $args
	 *
	 * @return  mixed
	 */
	public static function __callStatic($name, $args = array())
	{
		return call_user_func_array(array(static::getInstance(), $name), $args);
	}

	/**
	 * Method to get property Container
	 *
	 * @return  Container
	 */
	protected static function getContainer()
	{
		return static::$container = Ioc::factory(static::$name);
	}

	/**
	 * Method to set property container
	 *
	 * @param   Container $container
	 *
	 * @return  void
	 */
	public static function setContainer($container)
	{
		self::$container = $container;
	}

	/**
	 * reset
	 *
	 * @return  void
	 */
	public static function reset()
	{
		static::$instance = null;
	}

	/**
	 * Method to get property Key
	 *
	 * @return  string
	 */
	public static function getKey()
	{
		return static::$key;
	}

	/**
	 * Method to set property key
	 *
	 * @param   string $key
	 *
	 * @return  void
	 */
	public static function setKey($key)
	{
		self::$key = $key;
	}

	/**
	 * Method to get property Name
	 *
	 * @return  string
	 */
	public static function getName()
	{
		return static::$name;
	}

	/**
	 * Method to set property name
	 *
	 * @param   string $name
	 *
	 * @return  void
	 */
	public static function setName($name)
	{
		self::$name = $name;
	}
}
 