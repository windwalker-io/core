<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Windwalker\Cache\Cache;
use Windwalker\Core\Cache\CacheFactory;
use Windwalker\Core\Cache\CacheManager;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Structure\Structure;

/**
 * The CacheProvider class.
 * 
 * @since  2.0
 */
class CacheProvider implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container $container The DI container.
	 *
	 * @return  void
	 */
	public function register(Container $container)
	{
		// Get cache factory object.
		$container->share(CacheManager::class, function(Container $container)
		{
			return $container->createSharedObject(CacheManager::class);
		});

		// Get global cache object.
		$container->share(Cache::class, function(Container $container)
		{
			/** @var Structure $config */
			$config = $container->get('config');

			$storage = $config->get('cache.storage', 'file');
			$handler = $config->get('cache.serializer', 'php');

			return $container->get('cache.manager')->create('windwalker', $storage, $handler);
		});
	}
}
