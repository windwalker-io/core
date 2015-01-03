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
		$closure = function(Container $container)
		{
			$config  = $container->get('system.config');
			$options = array();

			$enabled = $config->get('cache.enabled', false);
			$debug   = $config->get('system.debug', false);

			$storage = $config->get('cache.storage', 'file');
			$handler = $config->get('cache.handler', 'serialized');

			$storage = ($enabled && !$debug) ? $storage : 'null';

			// Options
			$options['cache_time'] = $config->get('cache.time');

			if ($storage == 'file')
			{
				$options['cache_dir'] = $config->get('cache.dir');
			}

			return CacheFactory::getCache('windwalker', $storage, $handler, $options);
		};

		$container->share('system.cache', $closure)
			->alias('cache', 'system.cache');
	}
}
 