<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Composer\Autoload\ClassLoader;
use Windwalker\Core\Application\Offline\OfflineManager;
use Windwalker\Core\Runtime\Config;
use Windwalker\Core\Runtime\Runtime;
use Windwalker\Core\Service\FilterService;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Filter\FilterFactory;

/**
 * The RuntimeProvider class.
 */
class RuntimeProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container): void
    {
        $config = $container->getParameters();

        $container->share(Config::class, $config);
        $container->share(Container::class, $container);

        $loader = include Runtime::getRootDir() . '/vendor/autoload.php';
        $container->share(ClassLoader::class, $loader);

        $this->registerFilters($container);

        $container->prepareSharedObject(OfflineManager::class);
    }

    protected function registerFilters(Container $container): void
    {
        $container->prepareSharedObject(FilterFactory::class);
        $container->prepareSharedObject(FilterService::class);
    }
}
