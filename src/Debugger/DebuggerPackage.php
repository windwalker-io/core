<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Debugger;

use Composer\InstalledVersions;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\DateTime\ChronosService;
use Windwalker\Core\Events\Web\BeforeRequestEvent;
use Windwalker\Core\Manager\DatabaseManager;
use Windwalker\Core\Manager\Event\InstanceCreatedEvent;
use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageInstaller;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Data\Collection;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\Event\QueryEndEvent;
use Windwalker\Database\Event\QueryStartEvent;
use Windwalker\Database\Platform\AbstractPlatform;
use Windwalker\DI\BootableDeferredProviderInterface;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Event\Attributes\EventSubscriber;
use Windwalker\Event\Attributes\ListenTo;
use Windwalker\Filesystem\Path;
use Windwalker\Query\Query;
use Windwalker\Utilities\Reflection\BacktraceHelper;

/**
 * The DebuggerPackage class.
 */
#[EventSubscriber]
class DebuggerPackage extends AbstractPackage implements ServiceProviderInterface, BootableDeferredProviderInterface
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

        $container->share(
            'debugger.collector',
            new Collection()
        );

        $app = $container->get(ApplicationInterface::class);
        
        if ($app instanceof WebApplication) {
            $app->subscribe($this);
        }
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

    public function install(PackageInstaller $installer): void
    {
        //
    }

    #[ListenTo(BeforeRequestEvent::class)]
    public function beforeRequest(BeforeRequestEvent $event): void
    {
        $container = $event->getContainer();
        $collector = $container->get('debugger.collector');

        // Collect DB
        if (InstalledVersions::isInstalled('windwalker/database')) {
            $this->collectDatabase($container, $collector);
        }
    }

    /**
     * collectDatabase
     *
     * @param  Container   $container
     * @param  Collection  $collector
     *
     * @return  void
     */
    protected function collectDatabase(Container $container, Collection $collector): void
    {
        // $manager = $container->get(DatabaseManager::class);
        $container->extend(
            DatabaseManager::class,
            function (DatabaseManager $manager) use ($collector) {
                $manager->on(
                    InstanceCreatedEvent::class,
                    function (InstanceCreatedEvent $event) use ($collector) {
                        $name = $event->getInstanceName();
                        $dbCollector = $collector->proxy('db.queries');
                        $startTime   = null;
                        $memory = null;

                        /** @var DatabaseAdapter $db */
                        $db = $event->getInstance();

                        $db->on(
                            QueryStartEvent::class,
                            function (QueryStartEvent $event) use (&$startTime, &$memory) {
                                $startTime = microtime(true);
                                $memory = memory_get_usage(false);
                            }
                        );

                        $db->on(
                            QueryEndEvent::class,
                            function (QueryEndEvent $event) use (&$startTime, $name, $dbCollector, &$memory, $db) {
                                if (str_starts_with(strtoupper($event->getSql()), 'EXPLAIN')) {
                                    return;
                                }

                                $data['time']        = microtime(true) - $startTime;
                                $data['memory']      = memory_get_usage(false) - $memory;
                                $data['raw_query']   = (string) $event->getQuery();
                                $data['debug_query'] = $event->getDebugQueryString();
                                $data['bounded']     = $event->getBounded();
                                $data['connection']  = $name;
                                $data['count']       = $event->getStatement()->countAffected();
                                $data['backtrace'] = BacktraceHelper::normalizeBacktraces(debug_backtrace());

                                unset($data['start_time']);

                                $query = $event->getQuery();

                                if (
                                    $db->getPlatform()->getName() === AbstractPlatform::MYSQL
                                    && $query instanceof Query
                                    && $query->getType() === Query::TYPE_SELECT
                                ) {
                                    $query = clone $query;
                                    $query->prefix('EXPLAIN');
                                    $data['explain'] = $db->prepare($query)->all();
                                }

                                $dbCollector->push($data);

                                $startTime = null;
                            }
                        );
                    }
                );

                return $manager;
            }
        );
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
