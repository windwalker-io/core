<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Windwalker\Core\Event\EventCollector;
use Windwalker\Core\Event\EventDispatcherRegistry;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Event\EventEmitter;

/**
 * The RequestProvider class.
 */
class RequestProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->share(
            Config::class,
            $container->getParameters()
        );

        $container->share(
            EventCollector::class,
            fn(Container $container) => new EventCollector($container->getParam('app.debug'))
        );

        $container->bind(
            EventEmitter::class,
            function (Container $container) {
                return $container->get(EventDispatcherRegistry::class)->createDispatcher(
                    $container->get(EventCollector::class)
                );
            }
        );
    }
}
