<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Windwalker\Authentication\Authentication;
use Windwalker\Authentication\AuthenticationInterface;
use Windwalker\Authorisation\Authorisation;
use Windwalker\Authorisation\AuthorisationInterface;
use Windwalker\Authorisation\PolicyProviderInterface;
use Windwalker\Core\Auth\AuthService;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

use function Windwalker\ref;

/**
 * The AuthProvider class.
 */
class AuthProvider implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param  Container  $container  The DI container.
     *
     * @return  void
     */
    public function register(Container $container): void
    {
        $container->prepareSharedObject(
            Authentication::class,
            function (Authentication $authen, Container $container) {
                foreach ($container->getParam('auth.authentication.methods') as $name => $method) {
                    $authen->addMethod($name, $container->resolve($method));
                }

                return $authen;
            }
        )
            ->alias(AuthenticationInterface::class, Authentication::class);

        $container->prepareSharedObject(
            Authorisation::class,
            function (Authorisation $auth, Container $container) {
                foreach ($container->getParam('auth.authorisation.policies') as $name => $policy) {
                    $policy = $container->resolve($policy);

                    if ($policy instanceof PolicyProviderInterface) {
                        $auth->registerPolicyProvider($policy);
                    } else {
                        $auth->addPolicy($name, $policy);
                    }
                }

                return $auth;
            }
        )
            ->alias(AuthorisationInterface::class, Authorisation::class);

        $container->prepareSharedObject(AuthService::class);
    }
}
