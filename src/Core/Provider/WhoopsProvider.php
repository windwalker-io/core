<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Provider;

use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as Whoops;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * Class WhoopsProvider
 *
 * @since 1.0
 */
class WhoopsProvider implements ServiceProviderInterface, BootableProviderInterface
{
    /**
     * boot
     *
     * @param Container $container
     *
     * @return  void
     */
    public function boot(Container $container)
    {
        $config = $container->get('config');

        if (!$config->get('system.debug')) {
            return;
        }

        $error = $container->get('error.handler');

        /**
         * @param \Exception|\Throwable $e
         *
         * @return  void
         */
        $handler = function ($e) use ($container) {
            /** @var Whoops $whoops */
            $whoops = $container->get('whoops');
            $whoops->allowQuit(false);
            $whoops->handleException($e);
        };

        $error->addHandler($handler, 'default');
    }

    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container $container The DI container.
     *
     * @return  void
     * @throws \InvalidArgumentException
     *
     * @since   1.0
     */
    public function register(Container $container)
    {
        $config = $container->get('config');

        if ($config->get('system.debug')) {
            $whoops = new Whoops();

            $handler = new PrettyPageHandler();
            $handler->setEditor($config->get('whoops.editor', 'phpstorm'));

            $whoops->pushHandler($handler);

            $container->share(Whoops::class, $whoops)
                ->alias('whoops', Whoops::class);

            $container->share(PrettyPageHandler::class, $handler)
                ->alias('whoops.handler', PrettyPageHandler::class);
        }
    }
}
