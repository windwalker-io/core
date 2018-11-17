<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2018 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Cache;

use Windwalker\Cache\Cache;
use Windwalker\Cache\Serializer\RawSerializer;
use Windwalker\Cache\Storage\ArrayStorage;

/**
 * RuntimeCacheTrait
 *
 * @since  {DEPLOY_VERSION}
 */
trait StaticRuntimeCacheTrait
{
    /**
     * Property cache.
     *
     * @var  Cache
     */
    protected static $cache;

    /**
     * getStoredId
     *
     * @param string $id
     *
     * @return  string
     */
    public static function getCacheId($id = null)
    {
        return sha1($id);
    }

    /**
     * getCacheInstance
     *
     * @return  Cache
     *
     * @since  __DEPLOY_VERSION__
     */
    public static function getCacheInstance()
    {
        if (static::$cache === null) {
            static::resetCache();
        }

        return static::$cache;
    }

    /**
     * getCache
     *
     * @param string $id
     *
     * @return  mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected static function getCache($id = null)
    {
        return static::getCacheInstance()->get(static::getCacheId($id));
    }

    /**
     * setCache
     *
     * @param string $id
     * @param mixed  $item
     *
     * @return  mixed
     * @throws \Exception
     */
    protected static function setCache($id = null, $item = null)
    {
        static::getCacheInstance()->set(static::getCacheId($id), $item);

        return $item;
    }

    /**
     * hasCache
     *
     * @param string $id
     *
     * @return  bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected static function hasCache($id = null)
    {
        return static::getCacheInstance()->exists(static::getCacheId($id));
    }

    /**
     * resetCache
     */
    public static function resetCache()
    {
        static::$cache = new Cache(new ArrayStorage(), new RawSerializer());
    }

    /**
     * fetch
     *
     * @param string   $id
     * @param callable $closure
     *
     * @return  mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected static function fetch($id, $closure)
    {
        return static::getCacheInstance()->call(static::getCacheId($id), $closure);
    }
}
