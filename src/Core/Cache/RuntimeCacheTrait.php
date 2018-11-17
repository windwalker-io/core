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
     * @since  __DEPLOY_VERSION__
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
     * fetch
     *
     * @param string   $id
     * @param callable $closure
     *
     * @return  mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function fetch($id, $closure)
    {
        return $this->getCacheInstance()->call($this->getCacheId($id), $closure);
    }
}
