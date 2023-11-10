<?php

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Windwalker\Authentication\Authentication;
use Windwalker\Authentication\AuthenticationInterface;
use Windwalker\Authorization\Authorization;
use Windwalker\Authorization\AuthorizationInterface;
use Windwalker\Authorization\PolicyProviderInterface;
use Windwalker\Core\Auth\AuthService;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

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
            Authorization::class,
            function (Authorization $auth, Container $container) {
                foreach ($container->getParam('auth.authorization.policies') as $name => $policy) {
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
            ->alias(AuthorizationInterface::class, Authorization::class);

        $container->prepareSharedObject(AuthService::class);
    }
}
