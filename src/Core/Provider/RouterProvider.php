<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Windwalker\Core\Router\MainRouter;
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
		$container->share(MatcherInterface::class, [$this, 'matcher']);

		$container->prepareSharedObject(MainRouter::class);
	}

	/**
	 * matcher
	 *
	 * @param Container $container
	 *
	 * @return  MatcherInterface
	 */
	public function matcher(Container $container)
	{
		$matcher = $container->get('config')->get('routing.matcher', 'default');

		$matcher = strtolower($matcher) == 'default' ? 'sequential' : $matcher;

		$class = sprintf('Windwalker\Router\Matcher\%sMatcher', ucfirst($matcher));

		if (!class_exists($class))
		{
			throw new \DomainException(sprintf('Router Matcher: %s not supported.', ucfirst($matcher)));
		}

		return $container->newInstance($class);
	}
}
