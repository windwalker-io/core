<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Debugger\Subscriber;

use Composer\InstalledVersions;
use Psr\Http\Message\ResponseInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\DateTime\ChronosService;
use Windwalker\Core\Events\Web\AfterRequestEvent;
use Windwalker\Core\Events\Web\BeforeAppDispatchEvent;
use Windwalker\Core\Events\Web\BeforeControllerDispatchEvent;
use Windwalker\Core\Events\Web\TerminatingEvent;
use Windwalker\Core\Manager\DatabaseManager;
use Windwalker\Core\Router\Router;
use Windwalker\Data\Collection;
use Windwalker\Data\Format\FormatRegistry;
use Windwalker\Debugger\Module\Dashboard\DashboardRepository;
use Windwalker\Debugger\Services\DebugConsole;
use Windwalker\DI\Attributes\Autowire;
use Windwalker\DI\Container;
use Windwalker\Event\Attributes\EventSubscriber;
use Windwalker\Event\Attributes\ListenTo;
use Windwalker\Http\Helper\HttpHelper;
use Windwalker\Session\Cookie\CookiesInterface;
use Windwalker\Session\Session;

use function Windwalker\collect;
use function Windwalker\ref;
use function Windwalker\uid;

/**
 * The DebuggerSubscriber class.
 */
#[EventSubscriber]
class DebuggerSubscriber
{
    protected bool $finished = false;

    /**
     * DebuggerSubscriber constructor.
     */
    public function __construct(
        protected Container $container,
        #[Autowire] protected DashboardRepository $dashboardRepository
    ) {
        //
    }

    #[ListenTo(TerminatingEvent::class)]
    public function onTerminating(
        TerminatingEvent $event
    ): void {
        //
    }

    // #[ListenTo(BeforeRequestEvent::class)]
    // public function beforeRequest(BeforeRequestEvent $event): void
    // {
    //     $container = $event->getContainer();
    //     $collector = $container->get('debugger.collector');
    //
    //     // Collect DB
    //     $this->collectDatabase($container, $collector);
    // }

    #[ListenTo(BeforeAppDispatchEvent::class)]
    public function beforeAppDispatch(
        BeforeAppDispatchEvent $event
    ): void {
        $container = $event->getContainer();
        $app       = $container->get(AppContext::class);
        $collector = $container->get('debugger.collector');

        $chronosService = $app->service(ChronosService::class);

        // System info
        $info         = [];
        $info['time'] = $chronosService->createLocal()->format('Y-m-d H:i:s');

        $collector['system'] = $info;
    }

    #[ListenTo(BeforeControllerDispatchEvent::class)]
    public function beforeControllerDispatch(
        BeforeControllerDispatchEvent $event
    ): void {
        /** @var AppContext $app */
        $app       = $this->container->get(AppContext::class);
        $collector = $this->container->get('debugger.collector');

        $routing['matched']    = $app->getMatchedRoute();
        $routing['routes']     = $app->service(Router::class)->getRoutes();
        $routing['controller'] = $event->getController();

        $collector['routing'] = $routing;
    }

    #[ListenTo(AfterRequestEvent::class)]
    public function afterRequest(
        AfterRequestEvent $event
    ): void {
        $container = $event->getContainer();
        $app       = $container->get(AppContext::class);

        /** @var Collection $collector */
        $collector = $this->container->get('debugger.collector');

        $http = [];

        $appReq = $app->getAppRequest();
        $req    = $appReq->getRequest();

        $http['request']        = [
            'headers' => $req->getHeaders(),
            'server' => $req->getServerParams(),
            'attributes' => $req->getAttributes(),
            'method' => $req->getMethod(),
            'uri' => $req->getUri(),
            'query' => $req->getQueryParams(),
            'cookies' => $req->getCookieParams(),
            'body' => $req->getParsedBody(),
            'version' => $req->getProtocolVersion(),
            'target' => $req->getRequestTarget(),
        ];
        $http['systemUri']      = $appReq->getSystemUri();
        $http['overrideMethod'] = $appReq->getOverrideMethod();
        $http['remoteIP']       = HttpHelper::getIp($req->getServerParams());

        $res = $event->getResponse();

        $http['response'] = [
            'status' => $res->getStatusCode(),
            'statusText' => $res->getReasonPhrase(),
            'headers' => $res->getHeaders(),
            'version' => $res->getProtocolVersion(),
        ];
        $http['state']    = $app->getState()->all();
        $http['session']  = $app->service(Session::class)->all();
        $http['cookies']  = $app->service(CookiesInterface::class)->getStorage();

        $collector['http'] = $http;

        $this->finishCollected($event->getResponse());
    }

    public function collectOthers(): void
    {
        /** @var AppContext $app */
        $app = $this->container->get(AppContext::class);

        /** @var Collection $collector */
        $collector = $this->container->get('debugger.collector');

        $collector->def('system', []);

        $systemCollector                      = $collector->proxy('system');
        $systemCollector['framework_version'] = InstalledVersions::getPrettyVersion('windwalker/framework');
        $systemCollector['core_version']      = InstalledVersions::getPrettyVersion('windwalker/core');
        $systemCollector['php_version']       = PHP_VERSION;
        $systemCollector['config']            = FormatRegistry::makeDumpable($this->container->getParameters()->dump(true));

        // Database
        $connections = $app->config('database.connections');
        $dbConns     = [];

        foreach ($connections as $connection => $factory) {
            $db = $app->service(DatabaseManager::class)->get($connection);
            $version = null;

            try {
                $version = $db->getDriver()->getVersion();
            } catch (\Throwable $e) {
                // No actions.
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

    protected function finishCollected(?ResponseInterface $response = null): void
    {
        if ($this->finished) {
            return;
        }

        try {
            /** @var ApplicationInterface $app */
            $app = $this->container->get(ApplicationInterface::class);

            if ($app->getClient() === ApplicationInterface::CLIENT_CONSOLE) {
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
            $this->dashboardRepository->deleteOldFiles(
                $this->container->getParam('debugger.cache.max_files') ?? 100
            );

            /** @var Collection $collector */
            $collector = $this->container->get('debugger.collector');

            $id = uid('ww_', true);
            $collector['id'] = $id;

            // Debug console
            $debugConsole = $this->container->newInstance(DebugConsole::class);
            $debugConsole->pushToPage($collector, $response);

            // Write new file
            $this->dashboardRepository->writeFile($id, $collector);
        } catch (\Throwable $e) {
            echo $e;
        }

        $this->finished = true;
    }

    public function __destruct()
    {
        $this->finishCollected();
    }
}
