<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core;

use Windwalker\Core\User\UserManager;
use Windwalker\DI\Container;
use Windwalker\String\StringNormalise;
use Windwalker\Uri\UriData;
use Windwalker\Utilities\ArrayHelper;

/**
 * The Factory class.
 * 
 * @since  2.0
 */
abstract class Ioc
{
	/**
	 * Property profile.
	 *
	 * @var  string
	 */
	protected static $profile = 'windwalker';

	/**
	 * Property instances.
	 *
	 * @var  Container[]
	 */
	protected static $instances = array();

	/**
	 * getInstance
	 *
	 * @param string $name
	 * @param string $profile
	 *
	 * @return Container
	 */
	public static function factory($name = null, $profile = null)
	{
		$profile = $profile ? : self::$profile;

		$profile = strtolower(StringNormalise::toDashSeparated($profile));

		// Get Root container.
		if (!isset(self::$instances[$profile]))
		{
			$container = new Container;

			$container->name    = 'windwalker.root';
			$container->profile = $profile;

			self::$instances[$profile] = $container;
		}

		$mainContainer = self::$instances[$profile];

		if (!$name)
		{
			return $mainContainer;
		}

		if (!$mainContainer->hasChild($name))
		{
			$subContainer = $mainContainer->createChild($name);

			$subContainer->name    = $name;
			$subContainer->profile = $profile;
		}

		return $mainContainer->getChild($name);
	}

	/**
	 * getContainer
	 *
	 * @param   string  $name
	 * @param   string  $profile
	 *
	 * @return  Container
	 */
	public static function getContainer($name = null, $profile = null)
	{
		return static::factory($name, $profile);
	}

	/**
	 * setContainer
	 *
	 * @param   string     $profile
	 * @param   Container  $container
	 *
	 * @return  void
	 */
	public static function setContainer($profile, Container $container)
	{
		$profile = $profile ? : self::$profile;

		$profile = strtolower(StringNormalise::toDashSeparated($profile));

		self::$instances[$profile] = $container;
	}

	/**
	 * setProfile
	 *
	 * @param string $name
	 *
	 * @return  void
	 */
	public static function setProfile($name = 'windwalker')
	{
		$name = strtolower(StringNormalise::toDashSeparated($name));

		self::$profile = $name;
	}

	/**
	 * Method to get property Profile
	 *
	 * @return  string
	 */
	public static function getProfile()
	{
		return self::$profile;
	}

	/**
	 * reset
	 *
	 * @param string $profile
	 *
	 * @return  void
	 */
	public static function reset($profile = null)
	{
		$profile = $profile ? : self::$profile;

		$profile = strtolower(StringNormalise::toDashSeparated($profile));

		if (isset(self::$instances[$profile]))
		{
			unset(self::$instances[$profile]);
		}
	}

	/**
	 * getApplication
	 *
	 * @return  \Windwalker\Core\Application\WebApplication|\Windwalker\Console\Console
	 */
	public static function getApplication()
	{
		return static::get('application');
	}

	/**
	 * getAuthenticate
	 *
	 * @return  \Windwalker\Authentication\Authentication
	 */
	public static function getAuthentication()
	{
		return static::get('authentication');
	}

	/**
	 * getUserManager
	 *
	 * @return  UserManager
	 */
	public static function getUserManager()
	{
		return static::get('user.manager');
	}

	/**
	 * getConfig
	 *
	 * @return  \Windwalker\Structure\Structure
	 */
	public static function getConfig()
	{
		return static::get('config');
	}

	/**
	 * getEnvironment
	 *
	 * @return  \Windwalker\Environment\WebEnvironment
	 */
	public static function getEnvironment()
	{
		return static::get('environment');
	}

	/**
	 * getInput
	 *
	 * @return  \Windwalker\IO\Input
	 */
	public static function getInput()
	{
		return static::get('input');
	}

	/**
	 * getDispatcher
	 *
	 * @return  \Windwalker\Event\Dispatcher
	 */
	public static function getDispatcher()
	{
		return static::get('dispatcher');
	}

	/**
	 * getSession
	 *
	 * @return  \Windwalker\Session\Session
	 */
	public static function getSession()
	{
		return static::get('session');
	}

	/**
	 * getCache
	 *
	 * @return  \Windwalker\Cache\Cache
	 */
	public static function getGlobalCache()
	{
		return static::get('cache');
	}

	/**
	 * getDB
	 *
	 * @return  \Windwalker\Database\Driver\AbstractDatabaseDriver
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
		return static::get('router');
	}

	/**
	 * getLanguage
	 *
	 * @return  \Windwalker\Language\Language
	 */
	public static function getLanguage()
	{
		return static::get('language');
	}

	/**
	 * getUriData
	 *
	 * @return  UriData
	 */
	public static function getUriData()
	{
		return static::get('uri');
	}

	/**
	 * getDebugger
	 *
	 * @return  \Whoops\Run
	 */
	public static function getDebugger()
	{
		return static::get('debugger');
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
		return static::factory($name)->share($key, $callback);
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
		return static::factory($child)->exists($key);
	}

	/**
	 * dump
	 *
	 * @param int    $level
	 * @param string $name
	 * @param string $profile
	 *
	 * @return string
	 */
	public static function dump($level = 10, $name = null, $profile = null)
	{
		return ArrayHelper::dump(static::factory($name, $profile), $level);
	}
}
