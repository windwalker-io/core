<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Windwalker\Authentication\Authentication;
use Windwalker\Core\Authentication\Method\DatabaseMethod;
use Windwalker\Core\Authentication\NullUserHandler;
use Windwalker\Core\Authentication\User;
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
			$auth = new Authentication;

			$dispatcher = $container->get('system.dispatcher');

			$dispatcher->triggerEvent('onLoadAuthenticationMethods', array('authentication' => $auth));

			return $auth;
		};

		$container->share('system.authentication', $closure)
			->alias('authentication', 'system.authentication')
			->alias('auth', 'system.authentication');

		User::setHandler(new NullUserHandler);
	}
}
