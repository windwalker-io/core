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
use Windwalker\Core\Event\EventDispatcher;
use Windwalker\Core\User\NullUserHandler;
use Windwalker\Core\User\UserHandlerInterface;
use Windwalker\Core\User\UserManager;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Event\DispatcherInterface;

/**
 * The AuthenticateProvider class.
 *
 * @since  2.0
 */
class UserProvider implements ServiceProviderInterface
{
    /**
     * Property dispatcher.
     *
     * @var  DispatcherInterface
     */
    protected $dispatcher;

    /**
     * UserProvider constructor.
     *
     * @param DispatcherInterface $dispatcher
     */
    public function __construct(DispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

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
            ->bindShared(AuthenticationInterface::class, Authentication::class);

        // Authorisation
        $container->share(Authorisation::class, [$this, 'authorisation'])
            ->bindShared(AuthorisationInterface::class, Authorisation::class);

        // User Handler
        $this->prepareHandler($container);

        // User Manager
        $container->prepareSharedObject(UserManager::class);
    }

    /**
     * authentication
     *
     * @param Container $container
     *
     * @return  AuthenticationInterface
     * @throws \Windwalker\DI\Exception\DependencyResolutionException
     */
    public function authentication(Container $container)
    {
        $auth = $container->newInstance(Authentication::class);

        foreach ((array) $container->get('config')->get('user.methods') as $name => $method) {
            if ($method !== false) {
                $auth->addMethod($name, $container->newInstance($method));
            }
        }

        /** @var EventDispatcher $dispatcher */
        $this->dispatcher->triggerEvent('onLoadAuthenticationMethods', ['auth' => $auth]);

        return $auth;
    }

    /**
     * authorisation
     *
     * @param Container $container
     *
     * @return  AuthorisationInterface
     * @throws \InvalidArgumentException
     */
    public function authorisation(Container $container)
    {
        $auth   = new Authorisation;
        $config = $container->get('config');

        foreach ((array) $config->get('user.policies') as $name => $policy) {
            if (is_subclass_of($policy, PolicyInterface::class)) {
                $auth->addPolicy($name, $container->newInstance($policy));
            } elseif (is_subclass_of($policy, PolicyProviderInterface::class)) {
                $auth->registerPolicyProvider($container->newInstance($policy));
            } elseif ($policy === false) {
                continue;
            } else {
                throw new \InvalidArgumentException(sprintf(
                    'Please register instance of %s or %s',
                    PolicyInterface::class,
                    PolicyProviderInterface::class
                ));
            }
        }

        /** @var EventDispatcher $dispatcher */
        $this->dispatcher->triggerEvent('onLoadAuthorisationPolicies', ['auth' => $auth]);

        return $auth;
    }

    /**
     * handler
     *
     * @param Container $container
     *
     * @return  UserHandlerInterface
     * @throws \UnexpectedValueException
     */
    public function prepareHandler(Container $container)
    {
        $handler = $container->get('config')->get('user.handler') ?: NullUserHandler::class;

        $container->bindShared(UserHandlerInterface::class, $handler);
    }
}
