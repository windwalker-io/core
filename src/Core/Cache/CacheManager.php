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
use Windwalker\DI\Container;
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
     * Property container.
     *
     * @var  Container
     */
    protected $container;

    /**
     * Class init.
     *
     * @param Config    $config
     * @param Container $container
     *
     * @since  3.0
     */
    public function __construct(Config $config, Container $container)
    {
        $this->config = $config;
        $this->container = $container;
    }

    /**
     * getCacheInstance
     *
     * @param string $profile
     * @param array  $options
     *
     * @return  CacheInterface
     *
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getCacheInstance(?string $profile = null, array $options = []): CacheInterface
    {
        $config = $this->config;

        $config = $config->extract('cache');

        $profile = $profile ?: $config->get('default', 'global');

        if ($config->exists('profiles')) {
            $config = $config->extract('profiles.' . $profile);
        }

        $options = array_merge([
            'name' => 'windwalker',
            'storage' => 'array',
            'serializer' => 'php',
            'force_enabled' => false
        ], $config->toArray(), $options);

        return $this->getCache(
            $options['name'],
            $options['storage'],
            $options['serializer'],
            $options
        );
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
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     *
     * @deprecated Use getCacheInstance() instead.
     */
    public function getCache(
        string $name = 'windwalker',
        string $storage = 'array',
        string $serializer = 'php',
        array $options = []
    ): CacheInterface {
        $config = $this->config;

        $debug   = $config->get('system.debug', false);
        $enabled = $config->get('cache.enabled', false);

        $forceEnabled = $options['force_enabled'] ?? false;

        if (!$forceEnabled && ($debug || !$enabled) && !$this->ignoreGlobal) {
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
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    public function create(
        string $name = 'windwalker',
        string $storage = 'array',
        string $serializer = 'php',
        array $options = []
    ): CacheInterface {
        $storage    = $storage ?: 'array';
        $serializer = $serializer ?: 'php';

        ksort($options);

        $hash = sha1($name . $storage . $serializer . serialize($options));

        if (!empty(static::$caches[$hash])) {
            return static::$caches[$hash];
        }

        $storage = $this->getStorage($storage, $options, $name);
        $handler = $this->getSerializer($serializer);

        $class = $this->cacheClass;
        $cache = $this->container->newInstance(
            $class,
            [$storage, $handler]
        );

        return static::$caches[$hash] = $cache;
    }

    /**
     * getGlobal
     *
     * @param bool $forceNew
     *
     * @return  CacheInterface
     */
    public function getGlobal(bool $forceNew = false): CacheInterface
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
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    public function getStorage(
        string $storage,
        array $options = [],
        string $name = 'windwalker'
    ): CacheItemPoolInterface {
        $class = class_exists($storage)
            ? $storage
            : sprintf('Windwalker\Cache\Storage\%sStorage', StringNormalise::toCamelCase($storage));

        if (!class_exists($class)) {
            throw new \DomainException(sprintf('Cache Storage: %s not supported.', ucfirst($storage)));
        }

        $config = Ioc::getConfig();

        $options['cache_time']  = $options['cache_time'] ?? $config->get('cache.time');
        $options['cache_dir']   = $options['cache_dir'] ?? $config->get('path.cache');
        $options['deny_access'] = $options['deny_access'] ?? $config->get('cache.denyAccess');

        // Convert seconds to minutes
        $options['cache_time'] *= 60;

        switch (strtolower($storage)) {
            case 'file':
            case 'php_file':
            case 'forever_file':
                $path       = $options['cache_dir'];
                $denyAccess = $options['deny_access'];

                if (!is_dir($path)) {
                    // Try add root
                    $path = $this->config->get('path.root') . '/' . $path;
                }

                if (is_dir($path)) {
                    $path = realpath($path);
                }

                $group = ($name === 'windwalker') ? null : $name;

                return $this->container->newInstance(
                    $class,
                    [$path, $group, $denyAccess, $options['cache_time'], $options]
                );
                break;

            case 'redis':
            case 'memcached':
                return $this->container->newInstance(
                    $class,
                    [null, $options['cache_time'], $options]
                );
                break;

            default:
                return $this->container->newInstance(
                    $class,
                    [$options['cache_time'], $options]
                );
                break;
        }
    }

    /**
     * getDataHandler
     *
     * @param string $serializer
     *
     * @return  SerializerInterface
     * @throws \ReflectionException
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    public function getSerializer(string $serializer): SerializerInterface
    {
        $class = class_exists($serializer)
            ? $serializer
            : sprintf('Windwalker\Cache\Serializer\%sSerializer', StringNormalise::toCamelCase($serializer));

        if (!class_exists($class)) {
            throw new \DomainException(sprintf('Cache Serializer: %s not supported.', ucfirst($serializer)));
        }

        return $this->container->newInstance($class);
    }

    /**
     * Method to get property IgnoreGlobal
     *
     * @param boolean $bool
     *
     * @return boolean
     */
    public function ignoreGlobal(?bool $bool = null): bool
    {
        if ($bool === null) {
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
    public function getCacheClass(): string
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
