<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Windwalker\Core\Authentication\UserManager;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The AuthenticateProvider class.
 * 
 * @since  2.0
 */
class AuthenticationProvider implements ServiceProviderInterface
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
			$auth = new UserManager;

			$dispatcher = $container->get('system.dispatcher');

			$dispatcher->triggerEvent('onLoadAuthenticationMethods', array('auth' => $auth));

			return $auth;
		};

		$container->share(UserManager::class, $closure)
			->alias(UserManager::class, 'system.user.manager')
			->alias('user.manager', 'system.user.manager')
			->alias('auth', 'system.user.manager');
	}
}
