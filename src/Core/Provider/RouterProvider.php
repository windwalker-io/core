<?php

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Windwalker\Core\Application\RootApplicationInterface;
use Windwalker\Core\Router\Router;
use Windwalker\DI\BootableProviderInterface;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\WebSocket\Application\WsRootApplicationInterface;
use Windwalker\WebSocket\Router\WsRouter;

/**
 * The RouterProvider class.
 */
class RouterProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function __construct(protected RootApplicationInterface $app)
    {
    }

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
        if ($this->app instanceof WsRootApplicationInterface) {
            $container->prepareSharedObject(
                WsRouter::class,
                function (WsRouter $router, Container $container) {
                    return $router->register($container->getParam('routing.routes'));
                }
            )
                ->alias(Router::class, WsRouter::class);
        } else {
            $container->prepareSharedObject(
                Router::class,
                function (Router $router, Container $container) {
                    return $router->register($container->getParam('routing.routes'));
                }
            );
        }
    }
}
