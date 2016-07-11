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
use Windwalker\Authorisation\PolicyInterface;
use Windwalker\Authorisation\PolicyProviderInterface;
use Windwalker\Core\User\NullUserHandler;
use Windwalker\Core\User\UserData;
use Windwalker\Core\User\UserDataInterface;
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
		$container->share(Authentication::class, [$this, 'authentication'])
			->alias(AuthenticationInterface::class, Authentication::class);

		// Authorisation
		$container->share(Authorisation::class, [$this, 'authorisation'])
			->alias(AuthorisationInterface::class, Authorisation::class);

		// User Handler
		$container->share(UserHandlerInterface::class, [$this, 'handler']);

		// User Manager
		$container->share(UserManager::class, [$this, 'handler']);
	}

	/**
	 * authentication
	 *
	 * @param Container $container
	 *
	 * @return  AuthenticationInterface
	 */
	public function authentication(Container $container)
	{
		$auth = $container->createSharedObject(Authentication::class);

		foreach ((array) $container->get('config')->get('user.methods') as $name => $method)
		{
			if (is_string($method) && class_exists($method))
			{
				$method = $container->createSharedObject($method);
			}

			$auth->addMethod($name, $method);
		}

		/** @var EventDispatcher $dispatcher */
		$dispatcher = $container->get('dispatcher');

		$dispatcher->triggerEvent('onLoadAuthenticationMethods', array('auth' => $auth));

		return $auth;
	}

	/**
	 * authorisation
	 *
	 * @param Container $container
	 *
	 * @return  AuthorisationInterface
	 */
	public function authorisation(Container $container)
	{
		$auth = new Authorisation;
		$config = $container->get('config');

		foreach ((array) $config->get('user.policies') as $name => $policy)
		{
			if (is_string($policy) && class_exists($policy))
			{
				$policy = $container->createSharedObject($policy);
			}

			if ($policy instanceof PolicyInterface)
			{
				$auth->addPolicy($name, $policy);
			}
			elseif ($policy instanceof PolicyProviderInterface)
			{
				$auth->registerPolicyProvider($policy);
			}
			elseif ($policy === false)
			{
				continue;
			}
			else
			{
				throw new \InvalidArgumentException(sprintf(
					'Please register instance of %s or %s',
					PolicyInterface::class,
					PolicyProviderInterface::class
				));
			}
		}

		/** @var EventDispatcher $dispatcher */
		$dispatcher = $container->get('dispatcher');

		$dispatcher->triggerEvent('onLoadAuthorisationPolicies', array('auth' => $auth));

		return $auth;
	}

	/**
	 * handler
	 *
	 * @param Container $container
	 *
	 * @return  UserHandlerInterface
	 */
	public function handler(Container $container)
	{
		$handler = $container->get('config')->get('user.handler');

		if (is_string($handler) && class_exists($handler))
		{
			$handler = $container->createSharedObject($handler);
		}

		if ($handler instanceof UserHandlerInterface)
		{
			return $handler;
		}

		return new NullUserHandler;
	}

	/**
	 * manager
	 *
	 * @param Container $container
	 *
	 * @return  UserManager
	 */
	public function manager(Container $container)
	{
		/** @var UserManager $manager */
		$manager = $container->createSharedObject(UserManager::class);

		$manager->setDispatcher($container->get('dispatcher'));

		return $manager;
	}
}
