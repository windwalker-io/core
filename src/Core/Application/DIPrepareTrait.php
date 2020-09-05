<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Application;

use Windwalker\DI\BootableDeferredProviderInterface;
use Windwalker\DI\BootableProviderInterface;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\Utilities\Assert\Assert;

use function Windwalker\DI\share;

/**
 * DIPrepareTrait
 *
 * @since  {DEPLOY_VERSION}
 */
trait DIPrepareTrait
{
    protected static function prepareDependencyInjection(array $config, Container $container): void
    {
        static::prepareBindings($config['bindings'] ?? [], $container);
        static::prepareProviders($config['providers'] ?? [], $container);
        static::prepareDIAliases($config['aliases'] ?? [], $container);
    }

    protected static function prepareBindings(array $config, Container $container): void
    {
        foreach ($config as $key => $value) {
            if (is_numeric($key)) {
                if (!is_string($value)) {
                    throw new DefinitionException(
                        sprintf(
                            'Binding classes must with a string key, %s given.',
                            Assert::describeValue($value)
                        )
                    );
                }

                $container->set($value, share($value));
            } else {
                $container->set($key, $value);
            }
        }
    }

    protected static function prepareProviders(array $config, Container $container): void
    {
        $bootDeferred = [];

        foreach ($config ?? [] as $provider) {
            if (is_string($provider)) {
                $provider = $container->newInstance($provider);
            }

            $container->registerServiceProvider($provider);

            if ($provider instanceof BootableProviderInterface) {
                $provider->boot($container);
            }

            if ($provider instanceof BootableDeferredProviderInterface) {
                $bootDeferred[] = $provider;
            }
        }

        foreach ($bootDeferred as $provider) {
            $provider->bootDeferred($container);
        }
    }

    protected static function prepareDIAliases(array $config, Container $container): void
    {
        foreach ($config as $alias => $id) {
            $container->alias($alias, $id);
        }
    }
}