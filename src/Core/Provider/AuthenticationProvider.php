<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
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

			$auth->addMethod('database', new DatabaseMethod);

			$dispatcher = $container->get('system.dispatcher');

			$dispatcher->triggerEvent('onLoadAuthenticateMethods', array('authenticate' => $auth));

			return $auth;
		};

		$container->share('system.authenticate', $closure)
			->alias('authenticate', 'system.authenticate')
			->alias('auth', 'system.authenticate');
	}

	/**
	 * prepareAlias
	 *
	 * @return  void
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
