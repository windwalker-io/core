<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core;

use Windwalker\DI\Container;
use Windwalker\Utilities\ArrayHelper;

/**
 * The Factory class.
 * 
 * @since  {DEPLOY_VERSION}
 */
abstract class Ioc
{
	/**
	 * Property container.
	 *
	 * @var Container
	 */
	public static $container;

	/**
	 * Property subContainers.
	 *
	 * @var  Container[]
	 */
	public static $subContainers = array();

	/**
	 * getInstance
	 *
	 * @param string $name
	 *
	 * @return  Container
	 */
	public static function factory($name = null)
	{
		// No name, return root container.
		if (!$name)
		{
			if (!(self::$container instanceof Container))
			{
				self::$container = new Container;
			}

			return self::$container;
		}

		// Has name, we return children container.
		if (empty(self::$subContainers[$name]) || !(self::$subContainers[$name] instanceof Container))
		{
			self::$subContainers[$name] = new Container(static::getContainer());
		}

		return self::$subContainers[$name];
	}

	/**
	 * getContainer
	 *
	 * @param string $name
	 *
	 * @return  Container
	 */
	public static function getContainer($name = null)
	{
		return self::factory($name);
	}

	/**
	 * getApplication
	 *
	 * @return  \Windwalker\Core\Application\WebApplication|\Windwalker\Console\Console
	 */
	public static function getApplication()
	{
		return static::get('system.application');
	}

	/**
	 * getAuthenticate
	 *
	 * @return  \Windwalker\Authenticate\Authenticate
	 */
	public static function getAuthenticate()
	{
		return static::get('system.authenticate');
	}

	/**
	 * getConfig
	 *
	 * @return  \Windwalker\Registry\Registry
	 */
	public static function getConfig()
	{
		return static::get('system.config');
	}

	/**
	 * getEnvironment
	 *
	 * @return  \Windwalker\Environment\Web\WebEnvironment
	 */
	public static function getEnvironment()
	{
		return static::get('system.environment');
	}

	/**
	 * getInput
	 *
	 * @return  \Windwalker\IO\Input
	 */
	public static function getInput()
	{
		return static::get('system.input');
	}

	/**
	 * getDispatcher
	 *
	 * @return  \Windwalker\Event\Dispatcher
	 */
	public static function getDispatcher()
	{
		return static::get('system.dispatcher');
	}

	/**
	 * getSession
	 *
	 * @return  \Windwalker\Session\Session
	 */
	public static function getSession()
	{
		return static::get('system.session');
	}

	/**
	 * getCache
	 *
	 * @return  \Windwalker\Cache\Cache
	 */
	public static function getCache()
	{
		return static::get('system.cache');
	}

	/**
	 * getDB
	 *
	 * @return  \Windwalker\Database\Driver\DatabaseDriver
	 */
	public static function getDatabase()
	{
		return static::get('database');
	}

	/**
	 * getRouter
	 *
	 * @return  \Windwalker\Router\Router
	 */
	public static function getRouter()
	{
		return static::get('system.router');
	}

	/**
	 * getLanguage
	 *
	 * @return  \Windwalker\Language\Language
	 */
	public static function getLanguage()
	{
		return static::get('system.language');
	}

	/**
	 * getDebugger
	 *
	 * @return  \Whoops\Run
	 */
	public static function getDebugger()
	{
		return static::get('system.debugger');
	}

	/**
	 * getLogger
	 *
	 * @return  \Psr\Log\NullLogger|\Monolog\Logger
	 */
	public static function getLogger()
	{
		return static::get('logger');
	}

	/**
	 * getPackage
	 *
	 * @param string $name
	 *
	 * @return  \Windwalker\Core\Package\AbstractPackage
	 */
	public static function getPackage($name)
	{
		return static::get('package.' . $name);
	}

	/**
	 * get
	 *
	 * @param string  $key
	 * @param string  $child
	 * @param bool    $forceNew
	 *
	 * @return  mixed
	 */
	public static function get($key, $child = null, $forceNew = false)
	{
		$container = static::getContainer($child);

		if (!$container->exists($key))
		{
			return null;
		}

		return $container->get($key, $forceNew);
	}

	/**
	 * Convenience method for creating shared keys.
	 *
	 * @param   string   $key      Name of dataStore key to set.
	 * @param   callable $callback Callable function to run when requesting the specified $key.
	 * @param   string   $name     Container name.
	 *
	 * @return  Container This object for chaining.
	 *
	 * @since    2.0
	 */
	public function share($key, $callback, $name = null)
	{
		return static::factory($name, $key, $callback);
	}

	/**
	 * getNewInstance
	 *
	 * @param string $key
	 * @param string $child
	 *
	 * @return  mixed
	 */
	public static function getNewInstance($key, $child = null)
	{
		return static::get($key, $child, true);
	}

	/**
	 * exists
	 *
	 * @param string $key
	 * @param string $child
	 *
	 * @return  boolean
	 */
	public static function exists($key, $child = null)
	{
		return static::getContainer($child)->exists($key);
	}

	/**
	 * dump
	 *
	 * @param int  $level
	 * @param null $name
	 *
	 * @return  string
	 */
	public static function dump($level = 10, $name = null)
	{
		return ArrayHelper::dump(static::factory($name), $level);
	}
}
