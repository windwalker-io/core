<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Windwalker\Cache\Cache;
use Windwalker\Cache\CacheInterface;
use Windwalker\Cache\Serializer\SerializerInterface;
use Windwalker\Core\Config\Config;
use Windwalker\Core\Ioc;
use Windwalker\String\StringNormalise;

/**
 * The CacheManager class.
 *
 * @since  3.0
 */
class CacheManager
{
	/**
	 * Property instances.
	 *
	 * @var  Cache[]
	 */
	protected static $caches = [];

	/**
	 * Property ignoreGlobal.
	 *
	 * @var  bool
	 */
	protected $ignoreGlobal = false;

	/**
	 * Property config.
	 *
	 * @var  Config
	 */
	protected $config;

	/**
	 * Property cacheClass.
	 *
	 * @var  string
	 */
	protected $cacheClass = Cache::class;

	/**
	 * Class init.
	 *
	 * @param   Config  $config
	 *
	 *  @since  3.0
	 */
	public function __construct(Config $config)
	{
		$this->config = $config;
	}

	/**
	 * Create cache object.
	 *
	 * @param string $name
	 * @param string $storage
	 * @param string $serializer
	 * @param array  $options
	 *
	 * @return  CacheInterface
	 * @throws \UnexpectedValueException
	 */
	public function getCache($name = 'windwalker', $storage = 'array', $serializer = 'php', $options = [])
	{
		$config = $this->config;

		$debug = $config->get('system.debug', false);
		$enabled = $config->get('cache.enabled', false);

		if (($debug || !$enabled) && !$this->ignoreGlobal)
		{
			$storage    = 'null';
			$serializer = 'raw';
		}

		return $this->create($name, $storage, $serializer, $options);
	}

	/**
	 * getCache
	 *
	 * @param string $name
	 * @param string $storage
	 * @param string $serializer
	 * @param array  $options
	 *
	 * @return  CacheInterface
	 */
	public function create($name = 'windwalker', $storage = 'array', $serializer = 'php', $options = [])
	{
		$storage    = $storage ? : 'array';
		$serializer = $serializer ? : 'php';

		ksort($options);

		$hash = sha1($name . $storage . $serializer . serialize($options));

		if (!empty(static::$caches[$hash]))
		{
			return static::$caches[$hash];
		}

		$storage = $this->getStorage($storage, $options, $name);
		$handler = $this->getSerializer($serializer);

		$class = $this->cacheClass;
		$cache = new $class($storage, $handler);

		return static::$caches[$hash] = $cache;
	}

	/**
	 * getGlobal
	 *
	 * @param bool $forceNew
	 *
	 * @return  CacheInterface
	 */
	public function getGlobal($forceNew = false)
	{
		return Ioc::get('cache', $forceNew);
	}

	/**
	 * getStorage
	 *
	 * @param string $storage
	 * @param array  $options
	 * @param string $name
	 *
	 * @return CacheItemPoolInterface
	 *
	 * @throws \UnexpectedValueException
	 * @throws \DomainException
	 */
	public function getStorage($storage, $options = [], $name = 'windwalker')
	{
		$class = sprintf('Windwalker\Cache\Storage\%sStorage', StringNormalise::toCamelCase($storage));

		if (!class_exists($class))
		{
			throw new \DomainException(sprintf('Cache Storage: %s not supported.', ucfirst($storage)));
		}

		$config = Ioc::getConfig();

		$options['cache_time']  = isset($options['cache_time'])  ? $options['cache_time']  : $config->get('cache.time');
		$options['cache_dir']   = isset($options['cache_dir'])   ? $options['cache_dir']   : $config->get('path.cache');
		$options['deny_access'] = isset($options['deny_access']) ? $options['deny_access'] : $config->get('cache.denyAccess');

		// Convert seconds to minutes
		$options['cache_time'] *= 60;

		switch (strtolower($storage))
		{
			case 'file':
			case 'php_file':
			case 'forever_file':
				$path = $options['cache_dir'];
				$denyAccess = $options['deny_access'];

				if (!is_dir($path))
				{
					// Try add root
					$path = $this->config->get('path.root') . '/' . $path;
				}

				if (is_dir($path))
				{
					$path = realpath($path);
				}

				$group = ($name === 'windwalker') ? null : $name;

				return new $class($path, $group, $denyAccess, $options['cache_time'], $options);
				break;

			case 'redis':
			case 'memcached':
				return new $class(null, $options['cache_time'], $options);
				break;

			default:
				return new $class($options['cache_time'], $options);
				break;
		}
	}

	/**
	 * getDataHandler
	 *
	 * @param $serializer
	 *
	 * @return  SerializerInterface
	 */
	public function getSerializer($serializer)
	{
		$class = sprintf('Windwalker\Cache\Serializer\%sSerializer', StringNormalise::toCamelCase($serializer));

		if (!class_exists($class))
		{
			throw new \DomainException(sprintf('Cache Serializer: %s not supported.', ucfirst($serializer)));
		}

		return new $class;
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
	 * Method to get property CacheClass
	 *
	 * @return  string
	 */
	public function getCacheClass()
	{
		return $this->cacheClass;
	}

	/**
	 * Method to set property cacheClass
	 *
	 * @param   string $cacheClass
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setCacheClass($cacheClass)
	{
		$this->cacheClass = $cacheClass;

		return $this;
	}
}
