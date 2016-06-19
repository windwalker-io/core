<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Asset;

/**
 * The ScriptManager class.
 *
 * @see  ScriptManager
 *
 * @since  3.0
 */
abstract class AbstractScript
{
	/**
	 * Property asset.
	 *
	 * @var  callable|ScriptManager
	 */
	public static $instance;

	/**
	 * inited
	 *
	 * @param   string $name
	 * @param   mixed  ...$data
	 *
	 * @return bool
	 */
	public static function inited($name, ...$data)
	{
		return static::getInstance()->inited($name, ...$data);
	}

	/**
	 * getInitedId
	 *
	 * @param   mixed  ...$data
	 *
	 * @return  string
	 */
	public static function getInitedId(...$data)
	{
		return static::getInstance()->getInitedId(...$data);
	}

	/**
	 * getAsset
	 *
	 * @return  AssetManager
	 */
	public static function getAsset()
	{
		return static::getInstance()->getAsset();
	}

	/**
	 * addStyle
	 *
	 * @param string $url
	 * @param string $version
	 * @param array  $attribs
	 *
	 * @return  static
	 */
	public static function addCSS($url, $version = null, $attribs = array())
	{
		return static::getAsset()->addCSS($url, $version, $attribs);
	}

	/**
	 * addScript
	 *
	 * @param string $url
	 * @param string $version
	 * @param array  $attribs
	 *
	 * @return  static
	 */
	public static function addJS($url, $version = null, $attribs = array())
	{
		return static::getAsset()->addJS($url, $version, $attribs);
	}

	/**
	 * internalStyle
	 *
	 * @param string $content
	 *
	 * @return  static
	 */
	public static function internalCSS($content)
	{
		return static::getAsset()->internalCSS($content);
	}

	/**
	 * internalStyle
	 *
	 * @param string $content
	 *
	 * @return  static
	 */
	public static function internalJS($content)
	{
		return static::getAsset()->internalJS($content);
	}

	/**
	 * Handle dynamic, static calls to the object.
	 *
	 * @param   string  $method  The method name.
	 * @param   array   $args    The arguments of method call.
	 *
	 * @return  mixed
	 */
	public static function __callStatic($method, $args)
	{
		$instance = static::getInstance();

		switch (count($args))
		{
			case 0:
				return $instance->$method();
			case 1:
				return $instance->$method($args[0]);
			case 2:
				return $instance->$method($args[0], $args[1]);
			case 3:
				return $instance->$method($args[0], $args[1], $args[2]);
			case 4:
				return $instance->$method($args[0], $args[1], $args[2], $args[3]);
			default:
				return call_user_func_array(array($instance, $method), $args);
		}
	}

	/**
	 * getInstance
	 *
	 * @return  ScriptManager
	 */
	protected static function getInstance()
	{
		if (is_callable(static::$instance))
		{
			static::$instance = call_user_func(static::$instance);
		}

		if (!static::$instance instanceof ScriptManager)
		{
			throw new \LogicException('Instance of ScriptManager should be ' . ScriptManager::class);
		}

		return static::$instance;
	}
}
