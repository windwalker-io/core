<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2018 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Cache;

use Windwalker\Cache\Cache;
use Windwalker\Cache\Serializer\RawSerializer;
use Windwalker\Cache\Storage\ArrayStorage;

/**
 * RuntimeCacheTrait
 *
 * @since  3.4
 */
trait RuntimeCacheTrait
{
    /**
     * Property cache.
     *
     * @var  Cache
     */
    protected $cache;

    /**
     * getStoredId
     *
     * @param string $id
     *
     * @return  string
     */
    public function getCacheId($id = null)
    {
        return sha1($id);
    }

    /**
     * getCacheInstance
     *
     * @return  Cache
     *
     * @since  3.4.6
     */
    public function getCacheInstance()
    {
        if ($this->cache === null) {
            $this->resetCache();
        }

        return $this->cache;
    }

    /**
     * getCache
     *
     * @param string $id
     *
     * @return  mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function getCache($id = null)
    {
        return $this->getCacheInstance()->get($this->getCacheId($id));
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
    protected function setCache($id = null, $item = null)
    {
        $this->getCacheInstance()->set($this->getCacheId($id), $item);

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
    protected function hasCache($id = null)
    {
        return $this->getCacheInstance()->exists($this->getCacheId($id));
    }

    /**
     * resetCache
     *
     * @return  static
     */
    public function resetCache()
    {
        $this->cache = new Cache(new ArrayStorage(), new RawSerializer());

        return $this;
    }

    /**
     * Only get once if ID is same.
     *
     * @param string   $id
     * @param callable $closure
     * @param bool     $refresh
     *
     * @return  mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function once($id, $closure, $refresh = false)
    {
        $key = $this->getCacheId($id);
        $cache = $this->getCacheInstance();

        if ($refresh) {
            $cache->remove($key);
        }

        return $cache->call($key, $closure);
    }

    /**
     * Alias of once().
     *
     * @param string   $id
     * @param callable $closure
     * @param bool     $refresh
     *
     * @return  mixed
     * @throws \Psr\Cache\InvalidArgumentException
     *
     * @deprecated  Use once() instead.
     */
    protected function fetch($id, $closure, $refresh = false)
    {
        return $this->once($id, $closure, $refresh);
    }
}
