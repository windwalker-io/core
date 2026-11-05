<?php

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Windwalker\Core\Application\AppEnvironment;
use Windwalker\Core\Application\AppEnvironmentInterface;
use Windwalker\Core\Application\SecretKeeper;
use Windwalker\Core\Application\SecretKeeperInterface;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * Registers the default config-based environment services.
 *
 * Services already registered for the interfaces (e.g. a user's own
 * config-free implementation) are never overridden.
 */
class AppEnvironmentProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        if (!$container->has(AppEnvironmentInterface::class)) {
            $container->share(
                AppEnvironment::class,
                fn(Container $container) => new AppEnvironment($container->getParameters())
            )->alias(AppEnvironmentInterface::class, AppEnvironment::class);
        }

        if (!$container->has(SecretKeeperInterface::class)) {
            $container->share(
                SecretKeeper::class,
                fn(Container $container) => new SecretKeeper($container->getParameters())
            )->alias(SecretKeeperInterface::class, SecretKeeper::class);
        }
    }
}
