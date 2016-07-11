<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Windwalker\Cache\Cache as WindwalkerCache;
use Windwalker\Cache\Serializer\SerializerInterface;
use Windwalker\Core\Facade\AbstractProxyFacade;

/**
 * The Cache class.
 * 
 * @see  \Windwalker\Cache\Cache
 *
 * @method  static  mixed            get($key)
 * @method  static  WindwalkerCache  set($key, $val)
 * @method  static  boolean          remove($key)
 * @method  static  boolean          clear()
 * @method  static  array            getMultiple(array $keys)
 * @method  static  WindwalkerCache  setMultiple(array $items)
 * @method  static  WindwalkerCache  removeMultiple(array $keys)
 * @method  static  mixed            call($key, $callable)
 * @method  static  bool             exists($key)
 * @method  static  SerializerInterface     getSerializer()
 * @method  static  WindwalkerCache         setSerializer($serializer)
 * @method  static  WindwalkerCache         setStorage($storage)
 * @method  static  CacheItemPoolInterface  getStorage()
 *
 * @since  {DEPLOY_VERSION}
 */
class Cache extends AbstractProxyFacade
{
	/**
	 * Property _key.
	 *
	 * @var  string
	 */
	protected static $_key = 'cache';
}
