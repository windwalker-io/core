<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Cache;

use Windwalker\Cache\Cache;
use Windwalker\Cache\DataHandler\DataHandlerInterface;
use Windwalker\Cache\Storage\CacheStorageInterface;
use Windwalker\Core\Ioc;
use Windwalker\DI\Container;
use Windwalker\Registry\Registry;
use Windwalker\Utilities\ArrayHelper;

/**
 * The CacheFactory class.
 * 
 * @since  2.0
 */
class CacheFactory
{
	/**
	 * Property instances.
	 *
	 * @var  Cache[]
	 */
	protected static $instances = array();

	/**
	 * Property container.
	 *
	 * @var  Registry
	 */
	protected $config;

	/**
	 * Property ignoreGlobal.
	 *
	 * @var  bool
	 */
	protected $ignoreGlobal = false;

	/**
	 * Class init.
	 *
	 * @param  $config  Registry  The Config object.
	 *
	 * @since  2.0.5
	 */
	public function __construct(Registry $config = null)
	{
		$this->config = $config ? : Ioc::getConfig();
	}

	/**
	 * Create cache object.
	 *
	 * @param string $name
	 * @param string $storage
	 * @param string $dataHandler
	 * @param array  $options
	 *
	 * @return  Cache
	 */
	public function create($name = 'windwalker', $storage = 'runtime', $dataHandler = 'serialize', $options = array())
	{
		$debug = $this->config->get('system.debug', false);
		$enabled = $this->config->get('cache.enabled', false);

		if (($debug || !$enabled) && !$this->ignoreGlobal)
		{
			$storage = 'null';
			$dataHandler = 'string';
		}

		return static::getCache($name, $storage, $dataHandler, $options);
	}

	/**
	 * getCache
	 *
	 * @param string $name
	 * @param string $storage
	 * @param string $dataHandler
	 * @param array  $options
	 *
	 * @return  Cache
	 */
	public static function getCache($name = 'windwalker', $storage = 'runtime', $dataHandler = 'serialize', $options = array())
	{
		$storage = $storage ? : 'runtime';
		$dataHandler = $dataHandler ? : 'serialize';

		$hash = sha1($name . $storage . $dataHandler . serialize($options));

		if (!empty(static::$instances[$hash]))
		{
			return static::$instances[$hash];
		}

		$storage = static::getStorage($storage, $options, $name);
		$handler = static::getDataHandler($dataHandler);

		$cache = new Cache($storage, $handler);

		return static::$instances[$hash] = $cache;
	}

	/**
	 * getStorage
	 *
	 * @param string   $storage
	 * @param array    $options
	 * @param string   $name
	 *
	 * @return CacheStorageInterface
	 */
	public static function getStorage($storage, $options = array(), $name = 'windwalker')
	{
		$class = sprintf('Windwalker\Cache\Storage\%sStorage', ucfirst($storage));

		if (!class_exists($class))
		{
			throw new \DomainException(sprintf('Cache Storage: %s not supported.', ucfirst($storage)));
		}

		$config = Ioc::getConfig();

		$ttl = isset($options['cache_time']) ? $options['cache_time'] : $config->get('cache.time');

		switch (strtolower($storage))
		{
			case 'file':
				$path = isset($options['cache_dir']) ? $options['cache_dir'] : $config->get('cache.dir');
				$denyAccess = isset($options['deny_access']) ? $options['deny_access'] : $config->get('cache.denyAccess');

				if (!is_dir($path))
				{
					$path = Ioc::getEnvironment()->server->getRoot() . '/../' . $path;
				}

				if (is_dir($path))
				{
					$path = realpath($path);
				}

				$group = ($name == 'windwalker') ? null : $name;

				return new $class($path, $group, $denyAccess, $ttl, $options);
				break;

			case 'redis':
			case 'memcached':
				return new $class(null, $ttl, $options);
				break;

			default:
				return new $class($ttl, $options);
				break;
		}
	}

	/**
	 * getDataHandler
	 *
	 * @param $handler
	 *
	 * @return  DataHandlerInterface
	 */
	public static function getDataHandler($handler)
	{
		$class = sprintf('Windwalker\Cache\DataHandler\%sHandler', ucfirst($handler));

		if (!class_exists($class))
		{
			throw new \DomainException(sprintf('Cache Data Handler: %s not supported.', ucfirst($handler)));
		}

		return new $class;
	}

	/**
	 * Method to get property Config
	 *
	 * @return  Registry
	 */
	public function getConfig()
	{
		return $this->config;
	}

	/**
	 * Method to set property config
	 *
	 * @param   Registry $config
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setConfig($config)
	{
		$this->config = $config;

		return $this;
	}

	/**
	 * Method to get property IgnoreGlobal
	 *
	 * @param boolean $bool
	 *
	 * @return boolean
	 */
	public function ignoreGlobal($bool = null)
	{
		if ($bool === null)
		{
			return $this->ignoreGlobal;
		}

		$this->ignoreGlobal = (bool) $bool;

		return $bool;
	}

	/**
	 * __get
	 *
	 * @param string $name
	 *
	 * @return  mixed
	 */
	public function __get($name)
	{
		if ($name == 'config')
		{
			return $this->$name;
		}

		throw new \LogicException('Property ' . $name . ' acnnot access.');
	}
}
