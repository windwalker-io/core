<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Windwalker\Core\Router\Router;
use Windwalker\DI\BootableProviderInterface;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The RouterProvider class.
 */
class RouterProvider implements ServiceProviderInterface, BootableProviderInterface
{
    /**
     * @inheritDoc
     */
    public function boot(Container $container): void
    {
        /*
         * Pre-load Router here to cache it, that every child process will not crate new one.
         *
         * Since PHP has a memory leak bug on declaring anonymous function in included files.
         * Loading our routes files will cause memory usage higher and higher. So we load router here
         * to preload all routes and cache them.
         *
         * This BUG sill exists in PHP 8.1. @see https://bugs.php.net/bug.php?id=76982
         */
        $container->get(Router::class);
    }

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
