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
		$this->prepareAlias();

		$closure = function(Container $container)
		{
			$auth = new Authentication;

			$dispatcher = $container->get('system.dispatcher');

			$dispatcher->triggerEvent('onLoadAuthenticationMethods', array('authentication' => $auth));

			// 2.0 legacy
			$dispatcher->triggerEvent('onLoadAuthenticateMethods', array('authenticate' => $auth));

			return $auth;
		};

		$container->share('system.authentication', $closure)
			->alias('authentication', 'system.authentication')
			->alias('auth', 'system.authentication');

		// Legacy 2.0
		$container->alias('system.authenticate', 'system.authentication')
			->alias('authenticate', 'system.authentication');
	}

	/**
	 * Prepare alias for 2.0 legacy.
	 *
	 * @return  void
	 *
	 * @deprecated  3.0
	 */
	protected function prepareAlias()
	{
		static $executed;

		if ($executed)
		{
			return;
		}

		// Make Authentication legacy classes work
		class_alias('Windwalker\Core\Authentication\User',     'Windwalker\Core\Authenticate\User');
		class_alias('Windwalker\Core\Authentication\UserData', 'Windwalker\Core\Authenticate\UserData');
		class_alias('Windwalker\Core\Authentication\UserDataInterface',     'Windwalker\Core\Authenticate\UserDataInterface');
		class_alias('Windwalker\Core\Authentication\UserHandlerInterface',  'Windwalker\Core\Authenticate\UserHandlerInterface');
		class_alias('Windwalker\Core\Authentication\Method\DatabaseMethod', 'Windwalker\Core\Authenticate\DatabaseMethod');
		class_alias('Windwalker\Core\Authentication\Exception\AuthenticateException', 'Windwalker\Core\Authenticate\Exception\AuthenticateException');

		$executed = true;
	}
}
