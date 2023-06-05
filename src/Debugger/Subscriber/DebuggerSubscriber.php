<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Debugger\Subscriber;

use Composer\InstalledVersions;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Application\RootApplicationInterface;
use Windwalker\Core\DateTime\ChronosService;
use Windwalker\Core\Event\EventCollector;
use Windwalker\Core\Events\Web\AfterControllerDispatchEvent;
use Windwalker\Core\Events\Web\AfterRequestEvent;
use Windwalker\Core\Events\Web\AfterRespondEvent;
use Windwalker\Core\Events\Web\AfterRoutingEvent;
use Windwalker\Core\Events\Web\BeforeAppDispatchEvent;
use Windwalker\Core\Events\Web\BeforeControllerDispatchEvent;
use Windwalker\Core\Events\Web\BeforeRequestEvent;
use Windwalker\Core\Manager\DatabaseManager;
use Windwalker\Core\Manager\Event\InstanceCreatedEvent;
use Windwalker\Core\Profiler\Profiler;
use Windwalker\Core\Profiler\ProfilerFactory;
use Windwalker\Core\Router\Router;
use Windwalker\Data\Collection;
use Windwalker\Data\Format\FormatRegistry;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\Event\QueryEndEvent;
use Windwalker\Database\Event\QueryStartEvent;
use Windwalker\Database\Platform\AbstractPlatform;
use Windwalker\Debugger\Module\Dashboard\DashboardRepository;
use Windwalker\Debugger\Services\DebugConsole;
use Windwalker\DI\Attributes\Autowire;
use Windwalker\DI\Container;
use Windwalker\Event\Attributes\EventSubscriber;
use Windwalker\Event\Attributes\ListenTo;
use Windwalker\Http\Helper\HttpHelper;
use Windwalker\Http\Output\OutputInterface;
use Windwalker\Query\Query;
use Windwalker\Session\Cookie\CookiesInterface;
use Windwalker\Session\Session;
use Windwalker\Utilities\Reflection\BacktraceHelper;

use function Windwalker\uid;

/**
 * The DebuggerSubscriber class.
 */
#[EventSubscriber]
class DebuggerSubscriber
{
    protected bool $finished = false;

    public bool $disableCollection = false;

    protected Profiler $profiler;

    /**
     * @var Collection|null
     */
    protected ?Collection $collector = null;

    /**
     * @var Collection|null
     */
    protected ?Collection $queue = null;

    /**
     * DebuggerSubscriber constructor.
     */
    public function __construct(
        protected Container $container,
        #[Autowire] protected DashboardRepository $dashboardRepository
    ) {
        //
    }

    #[ListenTo(BeforeRequestEvent::class)]
    public function beforeRequest(BeforeRequestEvent $event): void
    {
        $rootApp = $this->container->get(RootApplicationInterface::class);

        if ($rootApp->getClient() === $rootApp::CLIENT_CONSOLE) {
            $this->disableCollection = true;

            return;
        }

        $profilerFactory = $this->container->get(ProfilerFactory::class);
        $this->profiler = $profilerFactory->get('main');

        $this->profiler->mark('RequestStart', ['system']);

        $container = $event->getContainer();

        $container->share('debugger.collector', $collector = new Collection());
        $container->share('debugger.queue', $queue = new Collection());

        $this->collectDatabase($container, $queue, $collector);
    }

    #[ListenTo(AfterRoutingEvent::class)]
    public function afterRouting(AfterRoutingEvent $event): void
    {
        $matchedRoute = $event->getMatched();

        if (str_starts_with((string) $matchedRoute?->getPattern(), '/_debugger')) {
            $this->disableCollection = true;
        }

        $this->profiler->mark('AfterRouting', ['system']);
    }

    #[ListenTo(BeforeAppDispatchEvent::class)]
    public function beforeAppDispatch(
        BeforeAppDispatchEvent $event
    ): void {
        if ($this->disableCollection) {
            return;
        }

        $this->profiler->mark('BeforeAppDispatch', ['system']);

        $container = $event->getContainer();
        $app = $container->get(AppContext::class);
        $collector = $this->getCollector();

        $chronosService = $app->service(ChronosService::class);

        // System info
        $info = [];
        $info['time'] = $chronosService->createLocal()->format('Y-m-d H:i:s');

        $collector['system'] = $info;
    }

    #[ListenTo(BeforeControllerDispatchEvent::class)]
    public function beforeControllerDispatch(
        BeforeControllerDispatchEvent $event
    ): void {
        if ($this->disableCollection) {
            return;
        }

        $this->profiler->mark('BeforeControllerDispatch');

        $this->getQueue()->push(
            function () use ($event) {
                /** @var AppContext $app */
                $app = $this->container->get(AppContext::class);
                $collector = $this->getCollector();

                $routing['matched'] = $app->getMatchedRoute();
                $routing['routes'] = $app->service(Router::class)->getRoutes();
                $routing['controller'] = $event->getController();

                $collector['routing'] = $routing;
            }
        );
    }

    #[ListenTo(AfterControllerDispatchEvent::class)]
    public function afterControllerDispatch(
        AfterControllerDispatchEvent $event
    ): void {
        if ($this->disableCollection) {
            return;
        }

        $this->profiler->mark('AfterControllerDispatch', ['system']);
    }

    #[ListenTo(AfterRequestEvent::class)]
    public function afterRequest(
        AfterRequestEvent $event
    ): void {
        $container = $event->getContainer();
        $app = $container->get(AppContext::class);

        $appReq = $app->getAppRequest();
        $matchedRoute = $appReq->getMatchedRoute();

        if (str_starts_with((string) $matchedRoute?->getPattern(), '/_debugger')) {
            $this->disableCollection = true;

            return;
        }

        $this->profiler->mark('AfterRequest', ['system']);

        $this->getQueue()->push(
            function () use ($app, $event, $appReq) {
                /** @var Collection $collector */
                $collector = $this->getCollector();

                $http = [];

                $req = $appReq->getRequest();

                $http['request'] = [
                    'headers' => $req->getHeaders(),
                    'server' => $req->getServerParams(),
                    'attributes' => $req->getAttributes(),
                    'method' => $req->getMethod(),
                    'uri' => $req->getUri(),
                    'query' => $req->getQueryParams(),
                    'cookies' => $req->getCookieParams(),
                    'body' => $req->getParsedBody(),
                    'files' => $req->getUploadedFiles(),
                    'version' => $req->getProtocolVersion(),
                    'target' => $req->getRequestTarget(),
                    'env' => $_ENV ?? [],
                ];
                $http['systemUri'] = $appReq->getSystemUri();
                $http['overrideMethod'] = $appReq->getOverrideMethod();
                $http['remoteIP'] = HttpHelper::getIp($req->getServerParams());

                $res = $event->getResponse();

                $http['response'] = [
                    'status' => $res->getStatusCode(),
                    'statusText' => $res->getReasonPhrase(),
                    'headers' => $res->getHeaders(),
                    'version' => $res->getProtocolVersion(),
                ];
                $http['state'] = $app->getState()->all();

                if (InstalledVersions::isInstalled('windwalker/session')) {
                    $http['session'] = $app->service(Session::class)->all();
                    $http['cookies'] = $app->service(CookiesInterface::class)->getStorage();
                }

                $collector['http'] = $http;

                $this->finishCollected($event->getResponse());
            }
        );
    }

    #[ListenTo(AfterRespondEvent::class)]
    public function afterRespond(AfterRespondEvent $event): void
    {
        $this->profiler->mark('AfterRequest', ['system']);

        foreach ($this->getQueue() as $handler) {
            $handler();
        }
    }

    public function collectOthers(): void
    {
        if ($this->disableCollection) {
            return;
        }

        /** @var AppContext $app */
        $app = $this->container->get(AppContext::class);
        $session = $this->container->get(Session::class);

        /** @var Collection $collector */
        $collector = $this->getCollector();

        $collector->def('system', []);

        $systemCollector = $collector->proxy('system');
        // Mostly we won't install whole framework
        // $systemCollector['framework_version'] = InstalledVersions::getPrettyVersion('windwalker/framework');
        $systemCollector['core_version'] = InstalledVersions::getPrettyVersion('windwalker/core');
        $systemCollector['php_version'] = PHP_VERSION;
        $systemCollector['messages'] = $session->getFlashBag()->peek();
        $systemCollector['config'] = FormatRegistry::makeDumpable($this->container->getParameters()->dump(true));

        // Timeline
        $profiler = $this->container->get(ProfilerFactory::class)->getInstances();

        $collector->setDeep('profiler', $profiler);

        // Events
        $eventCollector = $this->container->get(EventCollector::class);

        $collector->setDeep('events', [
            'invoked' => $eventCollector->getInvokedListeners(),
            'uninvoked' => $eventCollector->getUninvokedListeners(),
        ]);

        // Database
        $connections = $app->config('database.connections') ?? [];
        $dbConns = [];

        foreach ($connections as $connection => $factory) {
            try {
                $db = $app->service(DatabaseManager::class)->get($connection);
                $version = null;
                $version = $db->getDriver()->getVersion();
            } catch (Throwable $e) {
                // No actions.
                continue;
            }

            $dbConns[] = [
                'name' => $connection,
                'platform' => $db->getPlatform()->getName(),
                'driver' => $db->getOptions()['driver'],
                'version' => $version ?? '',
            ];
        }

        $collector->setDeep("db.connections", $dbConns);
    }

    /**
     * @param  Container  $container
     * @param  Collection  $queue
     * @param  Collection  $collector
     *
     * @return  void
     */
    protected function collectDatabase(Container $container, Collection $queue, Collection $collector): void
    {
        $container->extend(
            DatabaseManager::class,
            function (DatabaseManager $manager) use ($queue, $collector) {
                $manager->on(
                    InstanceCreatedEvent::class,
                    function (InstanceCreatedEvent $event) use ($queue, $collector) {
                        $name = $event->getInstanceName();
                        $dbCollector = $collector->proxy('db.queries.' . $name);
                        $startTime = null;
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
                            function (QueryEndEvent $event) use (
                                $queue,
                                &$startTime,
                                $name,
                                $dbCollector,
                                &$memory,
                                $db
                            ) {
                                if (str_starts_with(strtoupper($event->getSql()), 'EXPLAIN')) {
                                    return;
                                }

                                $data['time'] = microtime(true) - $startTime;
                                $data['memory'] = memory_get_usage(false) - $memory;
                                $data['count'] = $event->getStatement()->countAffected();
                                $backtrace = debug_backtrace();

                                $queue->push(
                                    function () use ($name, $event, $db, $dbCollector, $backtrace, &$data) {
                                        $data['raw_query'] = (string) $event->getQuery();
                                        $data['debug_query'] = $event->getDebugQueryString();
                                        $data['bounded'] = $event->getBounded();
                                        $data['connection'] = $name;
                                        $data['backtrace'] = BacktraceHelper::normalizeBacktraces($backtrace);

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
                                    }
                                );

                                $startTime = null;
                            }
                        );
                    }
                );

                return $manager;
            }
        );
    }

    protected function finishCollected(?ResponseInterface $response = null): void
    {
        if ($this->disableCollection) {
            return;
        }

        if ($this->finished) {
            return;
        }

        try {
            /** @var ApplicationInterface $app */
            $app = $this->container->get(ApplicationInterface::class);

            if ($app->getClientType() === 'console') {
                return;
            }

            $this->collectOthers();

            /** @var AppContext $app */
            $app = $this->container->get(AppContext::class);

            $matched = $app->getMatchedRoute();

            if ($matched?->getExtraValue('namespace') === 'debugger') {
                return;
            }

            // Delete old files
            $this->dashboardRepository->deleteOldRecords(
                $this->container->getParam('debugger.cache.max_files') ?? 100
            );

            /** @var Collection $collector */
            $collector = $this->getCollector();

            $id = uid('ww_', true);

            $collector['id'] = $id;

            // Debug console
            $debugConsole = $this->container->newInstance(DebugConsole::class);
            $output = $this->container->get(OutputInterface::class);
            $debugConsole->pushToPage($collector, $output, $response);

            // Write new file
            $this->dashboardRepository->writeFile($id, $collector);
        } catch (Throwable $e) {
            echo $e;
        }

        $this->finished = true;
    }

    public function getCollector(): Collection
    {
        return $this->collector ??= $this->container->get('debugger.collector');
    }

    public function getQueue(): Collection
    {
        return $this->queue ??= $this->container->get('debugger.queue');
    }
}
