<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Cache;

use Windwalker\Cache\Cache;
use Windwalker\Cache\DataHandler\DataHandlerInterface;
use Windwalker\Cache\Storage\CacheStorageInterface;
use Windwalker\Core\Ioc;
use Windwalker\DI\Container;
use Windwalker\DI\ContainerAwareInterface;
use Windwalker\Registry\Registry;
use Windwalker\Utilities\ArrayHelper;

/**
 * The CacheFactory class.
 * 
 * @since  2.0
 */
class CacheFactory implements ContainerAwareInterface
{
	/**
	 * Property instance.
	 *
	 * @var  static
	 */
	protected static $instance;

	/**
	 * Property instances.
	 *
	 * @var  Cache[]
	 */
	protected static $caches = array();

	/**
	 * Property ignoreGlobal.
	 *
	 * @var  bool
	 */
	protected $ignoreGlobal = false;

	/**
	 * Property container.
	 *
	 * @var  Container
	 */
	protected $container;

	/**
	 * Class init.
	 *
	 * @param Container $container
	 *
	 * @since  2.0.5
	 */
	public function __construct(Container $container = null)
	{
		$this->container = $container ? : $this->getContainer();
	}

	/**
	 * getInstance
	 *
	 * @param Container $container
	 *
	 * @return  static
	 */
	public static function getInstance(Container $container = null)
	{
		if (!static::$instance)
		{
			static::$instance = new CacheFactory($container);
		}

		return static::$instance;
	}

	/**
	 * setInstance
	 *
	 * @param   CacheFactory  $instance
	 *
	 * @return  void
	 */
	public static function setInstance(CacheFactory $instance)
	{
		static::$instance = $instance;
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
		$config = $this->container->get('system.config');

		$debug = $config->get('system.debug', false);
		$enabled = $config->get('cache.enabled', false);

		if (($debug || !$enabled) && !$this->ignoreGlobal)
		{
			$storage = 'null';
			$dataHandler = 'string';
		}

		$options['cache_time']  = isset($options['cache_time'])  ? $options['cache_time']  : $config->get('cache.time');
		$options['cache_dir']   = isset($options['cache_dir'])   ? $options['cache_dir']   : $config->get('cache.dir');
		$options['deny_access'] = isset($options['deny_access']) ? $options['deny_access'] : $config->get('cache.denyAccess');

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

		ksort($options);

		$hash = sha1($name . $storage . $dataHandler . serialize($options));

		if (!empty(static::$caches[$hash]))
		{
			return static::$caches[$hash];
		}

		$storage = static::getStorage($storage, $options, $name);
		$handler = static::getDataHandler($dataHandler);

		$cache = new Cache($storage, $handler);

		return static::$caches[$hash] = $cache;
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

		switch (strtolower($storage))
		{
			case 'file':
				$path = $options['cache_dir'];
				$denyAccess = ['deny_access'];

				if (!is_dir($path))
				{
					// Try add root
					$container = static::getInstance()->getContainer();
					$env = $container->get('system.environment');

					$path = $env->server->getRoot() . '/../' . $path;
				}

				if (is_dir($path))
				{
					$path = realpath($path);
				}

				$group = ($name == 'windwalker') ? null : $name;

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
	 * Get the DI container.
	 *
	 * @return  Container
	 *
	 * @throws  \UnexpectedValueException May be thrown if the container has not been set.
	 */
	public function getContainer()
	{
		if (!$this->container)
		{
			$this->container = Ioc::factory();
		}

		return $this->container;
	}

	/**
	 * Set the DI container.
	 *
	 * @param   Container $container The DI container.
	 *
	 * @return  static
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;

		return $this;
	}
}
