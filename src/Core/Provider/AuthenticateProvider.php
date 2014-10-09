<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Provider;

use Windwalker\Authenticate\Authenticate;
use Windwalker\Core\Auth\Method\DatabaseMethod;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The AuthenticateProvider class.
 * 
 * @since  {DEPLOY_VERSION}
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

			return $auth;
		};

		$container->share('system.authenticate', $closure)
			->alias('auth', 'system.authenticate');
	}
}
 