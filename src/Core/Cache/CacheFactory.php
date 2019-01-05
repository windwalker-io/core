<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Windwalker\Cache\CacheInterface;
use Windwalker\Cache\Serializer\SerializerInterface;
use Windwalker\Core\Facade\AbstractProxyFacade;

/**
 * The CacheFactory class.
 *
 * @see    CacheManager
 *
 * @method  static CacheInterface  getCache($name = 'windwalker', $storage = 'array', $serializer = 'php', $options = [])
 * @method  static CacheInterface  create($name = 'windwalker', $storage = 'array', $serializer = 'php', $options = [])
 * @method  static CacheInterface  getGlobal($forceNew = false)
 * @method  static CacheItemPoolInterface  getStorage($storage, $options = [], $name = 'windwalker')
 * @method  static SerializerInterface     getSerializer($serializer)
 * @method  static boolean       ignoreGlobal($bool = null)
 * @method  static string        getCacheClass()
 * @method  static CacheManager  setCacheClass($cacheClass)
 *
 * @since  2.0
 */
class CacheFactory extends AbstractProxyFacade
{
    protected static $_key = 'cache.manager';
}
