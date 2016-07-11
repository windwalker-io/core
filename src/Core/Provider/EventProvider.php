<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Windwalker\Core\Event\EventDispatcher;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Event\Dispatcher;
use Windwalker\Event\DispatcherInterface;

/**
 * The EventProvider class.
 * 
 * @since  2.0
 */
class EventProvider implements ServiceProviderInterface
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
			$dispatcher = new EventDispatcher;

			$dispatcher->setDebug($container->get('config')->get('system.debug'));

			return $dispatcher;
		};

		$container->share(EventDispatcher::class, $closure)
			->alias(Dispatcher::class, EventDispatcher::class)
			->alias(DispatcherInterface::class, EventDispatcher::class);
	}
}
