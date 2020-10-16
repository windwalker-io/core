<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Provider;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Controller\ControllerDispatcher;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The AppProvider class.
 *
 * @since  __DEPLOY_VERSION__
 */
class AppProvider implements ServiceProviderInterface
{
    protected ApplicationInterface $app;

    /**
     * AppProvider constructor.
     *
     * @param  ApplicationInterface  $app
     */
    public function __construct(ApplicationInterface $app)
    {
        $this->app = $app;
    }

    /**
     * @inheritDoc
     * @throws DefinitionException
     */
    public function register(Container $container): void
    {
        $container->share(Config::class, $container->getParameters());
        $container->share(Container::class, $container);
        $container->share(get_class($this->app), $this->app);
        $container->share(ApplicationInterface::class, $this->app);
    }
}
