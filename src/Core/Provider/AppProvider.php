<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\PathResolver;
use Windwalker\Core\Event\EventDispatcherRegistry;
use Windwalker\Core\Package\PackageRegistry;
use Windwalker\Core\Runtime\Config;
use Windwalker\Core\Schedule\ScheduleService;
use Windwalker\Core\Service\FilterService;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Event\EventEmitter;
use Windwalker\Filter\FilterFactory;

/**
 * The AppProvider class.
 *
 * @since  4.0
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
        $container->share(get_parent_class($this->app), $this->app);
        $container->share(ApplicationInterface::class, $this->app);
        $container->prepareSharedObject(PathResolver::class);
        $container->prepareSharedObject(PackageRegistry::class);

        $this->prepareEvents($container);

        $this->prepareUtilities($container);

        $container->mergeParameters(
            'di.attributes',
            require __DIR__ . '/../../../etc/attributes.php'
        );
    }

    /**
     * prepareEvents
     *
     * @param  Container  $container
     *
     * @return  void
     *
     * @throws DefinitionException
     */
    protected function prepareEvents(Container $container): void
    {
        $container->prepareSharedObject(EventDispatcherRegistry::class);

        $container->bind(
            EventEmitter::class,
            fn() => $container->get(EventDispatcherRegistry::class)->createDispatcher()
        );
    }

    protected function prepareUtilities(Container $container): void
    {
        $container->prepareSharedObject(FilterFactory::class);
        $container->prepareSharedObject(FilterService::class);
        $container->prepareSharedObject(ScheduleService::class);
    }
}
