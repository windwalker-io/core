<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Windwalker\Core\Router\Router;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The RouterProvider class.
 */
class RouterProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container): void
    {
        $container->prepareSharedObject(
            Router::class,
            function (Router $router, Container $container) {
                return $router->register($container->getParam('routing.routes'));
            }
        );
    }
}
