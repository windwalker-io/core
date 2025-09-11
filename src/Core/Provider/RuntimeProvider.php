<?php

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Composer\Autoload\ClassLoader;
use Windwalker\Core\Application\AppLayer;
use Windwalker\Core\Application\AppVerbosity;
use Windwalker\Core\Application\Offline\MaintenanceManager;
use Windwalker\Core\Runtime\Config;
use Windwalker\Core\Runtime\Runtime;
use Windwalker\Core\Service\FilterService;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Environment\Environment;
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
        $container->share(AppVerbosity::class, Runtime::$verbosity);
        $container->prepareSharedObject(Environment::class);

        $autoloadFile = Runtime::getRootDir() . '/vendor/autoload.php';

        if (is_file($autoloadFile)) {
            $loader = include $autoloadFile;
            $container->share(ClassLoader::class, $loader);
        }

        $this->registerFilters($container);

        $container->prepareSharedObject(MaintenanceManager::class);
    }

    protected function registerFilters(Container $container): void
    {
        $container->prepareSharedObject(FilterFactory::class);
        $container->prepareSharedObject(FilterService::class);
    }
}
