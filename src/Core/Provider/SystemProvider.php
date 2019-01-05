<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Provider;

use Symfony\Component\Dotenv\Dotenv;
use Windwalker\Core\Application\WindwalkerApplicationInterface;
use Windwalker\Core\Config\Config;
use Windwalker\Core\Package\PackageResolver;
use Windwalker\Core\Repository;
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

        $this->registerClassAlias();
    }

    /**
     * registerClassAlias
     *
     * @return  void
     */
    protected function registerClassAlias()
    {
        // Model to Repository
        class_alias(
            Repository\Exception\ValidateFailException::class,
            'Windwalker\Core\Model\Exception\ValidateFailException'
        );
        class_alias(Repository\Traits\CliOutputModelTrait::class, 'Windwalker\Core\Model\Traits\CliOutputModelTrait');
        class_alias(Repository\Traits\DatabaseModelTrait::class, 'Windwalker\Core\Model\Traits\DatabaseModelTrait');
        class_alias(
            Repository\Traits\DatabaseRepositoryTrait::class,
            'Windwalker\Core\Model\Traits\DatabaseRepositoryTrait'
        );
        class_alias(Repository\DatabaseRepository::class, 'Windwalker\Core\Model\DatabaseModelRepository');
        class_alias(Repository\DatabaseRepository::class, 'Windwalker\Core\Repository\DatabaseModelRepository');
        class_alias(
            Repository\DatabaseRepositoryInterface::class,
            'Windwalker\Core\Model\DatabaseRepositoryInterface'
        );
        class_alias(Repository\Repository::class, 'Windwalker\Core\Model\ModelRepository');
        class_alias(Repository\Repository::class, 'Windwalker\Core\Repository\ModelRepository');
    }
}
