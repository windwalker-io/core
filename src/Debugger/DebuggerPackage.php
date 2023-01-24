<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Debugger;

use Windwalker\Core\Application\RootApplicationInterface;
use Windwalker\Core\DI\RequestBootableProviderInterface;
use Windwalker\Core\Manager\DatabaseManager;
use Windwalker\Core\Manager\Event\InstanceCreatedEvent;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageInstaller;
use Windwalker\Data\Collection;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\Event\QueryEndEvent;
use Windwalker\Database\Event\QueryStartEvent;
use Windwalker\Database\Platform\AbstractPlatform;
use Windwalker\DI\BootableDeferredProviderInterface;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Event\Attributes\EventSubscriber;
use Windwalker\Filesystem\Path;
use Windwalker\Query\Query;
use Windwalker\Utilities\Reflection\BacktraceHelper;

/**
 * The DebuggerPackage class.
 */
#[EventSubscriber]
class DebuggerPackage extends AbstractPackage implements
    ServiceProviderInterface,
    BootableDeferredProviderInterface,
    RequestBootableProviderInterface
{
    public function register(Container $container): void
    {
        $container->mergeParameters(
            'renderer.paths',
            [
                Path::normalize(__DIR__ . '/../../views/debugger'),
            ],
            Container::MERGE_OVERRIDE
        );
    }

    /**
     * boot
     *
     * @param  Container  $container
     *
     * @return  void
     */
    public function bootDeferred(Container $container): void
    {
        /** @var Collection $collector */
        // $collector = $container->get('debugger.collector');
        //
        // $this->collectDatabase($container, $collector);
    }

    public function bootBeforeRequest(Container $container): void
    {
        $rootApp = $container->get(RootApplicationInterface::class);

        if ($rootApp->getClient() === $rootApp::CLIENT_CONSOLE) {
            return;
        }

        $container->share('debugger.collector', new Collection());

        $container->share('debugger.queue', new Collection());
    }

    public function install(PackageInstaller $installer): void
    {
        //
    }

    public static function getName(): string
    {
        return 'debugger';
    }

    protected static function loadComposerJson(): array
    {
        return [];
    }
}
