<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Windwalker\Core\Application\AppLayer;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\AppType;
use Windwalker\Core\Application\PathResolver;
use Windwalker\Core\Application\RootApplicationInterface;
use Windwalker\Core\CliServer\CliServerClient;
use Windwalker\Core\CliServer\CliServerStateManager;
use Windwalker\Core\Event\EventDispatcherRegistry;
use Windwalker\Core\Package\PackageRegistry;
use Windwalker\Core\Profiler\ProfilerFactory;
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
     * @param  ProfilerFactory|null  $profilerFactory
     */
    public function __construct(ApplicationInterface $app, protected ?ProfilerFactory $profilerFactory = null)
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
        $container->share($this->app::class, $this->app)
            ->alias(RootApplicationInterface::class, $this->app::class)
            ->alias(ApplicationInterface::class, $this->app::class)
            ->providedIn(AppLayer::APP);

        if ($parentClass = get_parent_class($this->app)) {
            $container->alias($parentClass, $this->app::class);
        }

        $container->prepareSharedObject(PathResolver::class);
        $container->prepareSharedObject(PackageRegistry::class);

        if ($this->profilerFactory) {
            $container->share(ProfilerFactory::class, $this->profilerFactory);
        } else {
            $container->prepareSharedObject(ProfilerFactory::class, null, Container::ISOLATION);
        }

        if ($this->app->getType() === AppType::CLI_WEB) {
            $this->prepareCliWeb($container);
        }

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
        $container->share(
            EventDispatcherRegistry::class,
            fn () => new EventDispatcherRegistry($this->app)
        )
            ->providedIn(AppLayer::REQUEST);
    }

    protected function prepareUtilities(Container $container): void
    {
        $container->prepareSharedObject(FilterFactory::class, options: Container::ISOLATION);
        $container->prepareSharedObject(FilterService::class, options: Container::ISOLATION);
        $container->prepareSharedObject(ScheduleService::class, options: Container::ISOLATION);
    }

    protected function prepareCliWeb(Container $container): void
    {
        $container->prepareSharedObject(CliServerClient::class);
    }
}
