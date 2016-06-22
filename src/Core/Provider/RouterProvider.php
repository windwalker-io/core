<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Windwalker\Core\Router\CoreRoute;
use Windwalker\Core\Router\CoreRouter;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Router\Matcher\MatcherInterface;

/**
 * The RouterProvider class.
 * 
 * @since  2.0
 */
class RouterProvider implements ServiceProviderInterface
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
		$self = $this;

		$closure = function(Container $container) use ($self)
		{
			/** @var \Windwalker\Registry\Registry $config */
			$config = $container->get('config');

			$matcher = $config->get('routing.matcher', 'default');

			$matcher = strtolower($matcher) == 'default' ? 'sequential' : $matcher;

			$router = new CoreRouter(array(), $self->getMatcher($matcher));

			$router->setUri($container->get('uri'))
				->setDispatcher($container->get('dispatcher'));

			return $router;
		};

		$container->share(CoreRouter::class, $closure)
			->alias('router', CoreRouter::class);

		$closure = function (Container $container)
		{
		    return $container->createObject(CoreRoute::class);
		};

		$container->share(CoreRoute::class, $closure)
			->alias('route', CoreRoute::class);
	}

	/**
	 * getMatcher
	 *
	 * @param   string  $matcher
	 *
	 * @return  MatcherInterface
	 */
	public function getMatcher($matcher)
	{
		$class = sprintf('Windwalker\Router\Matcher\%sMatcher', ucfirst($matcher));

		if (!class_exists($class))
		{
			throw new \DomainException(sprintf('Router Matcher: %s not supported.', ucfirst($matcher)));
		}

		return new $class;
	}
}
 