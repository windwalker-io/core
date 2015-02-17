<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Provider;

use Windwalker\Core\Cache\CacheFactory;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

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
		$closure = function(Container $container)
		{
			$config = $container->get('system.config');

			return new CacheFactory($config);
		};

		$container->share('system.cache.factory', $closure)
			->alias('cache.factory', 'system.cache');

		// Get global cache object.
		$container->share('system.cache', function(Container $container)
		{
			$config  = $container->get('system.config');

			$storage = $config->get('cache.storage', 'file');
			$handler = $config->get('cache.handler', 'serialized');

			return $container->get('system.cache.factory')->create('windwalker', $storage, $handler);
		})->alias('cache', 'system.cache');
	}
}
