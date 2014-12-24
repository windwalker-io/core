<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Test\Mvc\Provider;

use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The TestMvcProvider class.
 * 
 * @since  {DEPLOY_VERSION}
 */
class TestMvcProvider implements ServiceProviderInterface
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
		$container->share('flower.sakura', 'Flower Sakura');

		$closure = function(Container $container)
		{
			return $container->get('system.config');
		};

		$container->share('mvc.config', $closure);
	}
}
