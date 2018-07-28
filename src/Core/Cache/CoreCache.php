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
use Windwalker\Cache\Serializer\SerializerInterface;
use Windwalker\Core\Facade\AbstractProxyFacade;

/**
 * The Cache class.
 *
 * @see    \Windwalker\Cache\Cache
 *
 * @method  static mixed    get($key)
 * @method  static Cache    set($key, $val)
 * @method  static boolean  remove($key)
 * @method  static boolean  clear()
 * @method  static array    getMultiple(array $keys)
 * @method  static Cache  setMultiple(array $items)
 * @method  static Cache  removeMultiple(array $keys)
 * @method  static mixed  call($key, $callable)
 * @method  static bool   exists($key)
 * @method  static SerializerInterface  getSerializer()
 * @method  static Cache  setSerializer($serializer)
 * @method  static Cache  setStorage($storage)
 * @method  static CacheItemPoolInterface  getStorage()
 *
 * @since  3.0
 */
class CoreCache extends AbstractProxyFacade
{
    /**
     * Property _key.
     *
     * @var  string
     * phpcs:disable
    */
    protected static $_key = 'cache';
    // phpcs:enable
}
