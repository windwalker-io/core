<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Windwalker\Authenticate\Authenticate;
use Windwalker\Core\Authenticate\Method\DatabaseMethod;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The AuthenticateProvider class.
 * 
 * @since  2.0
 */
class AuthenticateProvider implements ServiceProviderInterface
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
			$auth = new Authenticate;

			$auth->addMethod('database', new DatabaseMethod);

			$dispatcher = $container->get('system.dispatcher');

			$dispatcher->triggerEvent('onLoadAuthenticateMethods', array('authenticate' => $auth));

			return $auth;
		};

		$container->share('system.authenticate', $closure)
			->alias('authenticate', 'system.authenticate')
			->alias('auth', 'system.authenticate');
	}
}
 