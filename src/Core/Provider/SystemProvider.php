<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Windwalker\Core\Application\WindwalkerApplicationInterface;
use Windwalker\Core\Config\Config;
use Windwalker\Core\Package\PackageResolver;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Structure\Structure;

/**
 * The SystemProvider class.
 *
 * @since  2.0
 */
class SystemProvider implements ServiceProviderInterface
{
    /**
     * Property app.
     *
     * @var WindwalkerApplicationInterface
     */
    protected $app;

    /**
     * Property config.
     *
     * @var  Structure
     */
    protected $config;

    /**
     * Class init.
     *
     * @param WindwalkerApplicationInterface $app
     * @param Structure                      $config
     */
    public function __construct(WindwalkerApplicationInterface $app, Structure $config)
    {
        $this->app    = $app;
        $this->config = $config;
    }

    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container $container The DI container.
     *
     * @return  void
     */
    public function register(Container $container)
    {
        $container->share(Container::class, $container);

        $container->share(get_class($this->app), $this->app)
            ->bindShared(WindwalkerApplicationInterface::class, get_class($this->app));

        $container->share(Config::class, $this->config);

        $container->prepareSharedObject(PackageResolver::class);
    }
}
