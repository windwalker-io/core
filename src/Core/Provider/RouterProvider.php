<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
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
     * Registers the service provider with a DI container.
     *
     * @param  Container  $container  The DI container.
     *
     * @return  void
     */
    public function register(Container $container): void
    {
        $this->registerRouter($container);
    }

    protected function registerRouter(Container $container): void
    {
        $container->prepareSharedObject(Router::class);
    }
}
