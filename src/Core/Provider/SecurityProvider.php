<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Provider;

use Windwalker\Core\Security\CsrfGuard;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The SecurityProvider class.
 *
 * @since  {DEPLOY_VERSION}
 */
class SecurityProvider implements ServiceProviderInterface
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
			return new CsrfGuard($container);
		};

		$container->share('system.security.csrf', $closure)
			->alias('security,csrf', 'system.security.csrf');
	}
}
