<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Windwalker\Authentication\Authentication;
use Windwalker\Authentication\AuthenticationInterface;
use Windwalker\Authorisation\Authorisation;
use Windwalker\Authorisation\AuthorisationInterface;
use Windwalker\Core\User\NullUserHandler;
use Windwalker\Core\User\UserHandlerInterface;
use Windwalker\Core\User\UserManager;
use Windwalker\Core\Event\EventDispatcher;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The AuthenticateProvider class.
 * 
 * @since  2.0
 */
class UserProvider implements ServiceProviderInterface
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
		// Authentication
		$closure = function(Container $container)
		{
			$auth = new Authentication;

			/** @var EventDispatcher $dispatcher */
			$dispatcher = $container->get('dispatcher');

			$dispatcher->triggerEvent('onLoadAuthenticationMethods', array('auth' => $auth));

			return $auth;
		};

		$container->share(Authentication::class, $closure)
			->alias('authentication', Authentication::class)
			->alias('authentication', Authentication::class)
			->alias(AuthenticationInterface::class, Authentication::class);

		// Authorisation
		$closure = function(Container $container)
		{
			$auth = new Authorisation;

			/** @var EventDispatcher $dispatcher */
			$dispatcher = $container->get('dispatcher');

			$dispatcher->triggerEvent('onLoadAuthorisationPolicies', array('auth' => $auth));

			return $auth;
		};

		$container->share(Authorisation::class, $closure)
			->alias('authorisation', Authorisation::class)
			->alias('authorisation', Authorisation::class)
			->alias(AuthorisationInterface::class, Authorisation::class);

		// User Handler
		$container->share(UserHandlerInterface::class, function ()
		{
			return new NullUserHandler;
		});

		// User Manager
		$closure = function(Container $container)
		{
			return $container->createObject(UserManager::class);
		};

		$container->share(UserManager::class, $closure)
			->alias('user.manager', UserManager::class)
			->alias('user.manager', UserManager::class);
	}
}
