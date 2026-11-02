<?php

declare(strict_types=1);

namespace Windwalker\Tests;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Events\Web\BeforeRequestEvent;
use Windwalker\Core\Migration\MigrationService;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Http\Event\RequestEvent;
use Windwalker\Http\Factory\ServerRequestFactory;

class TestApp extends WebApplication
{
    public static self $instance;

    public static AppContext $appContext;

    protected bool $dbInitialized = false;

    public function executeServerEvent(RequestEvent $event): ResponseInterface
    {
        $appContext = $this->createContextFromServerEvent($event);

        $event->endHandler = fn() => $this->stopContext($appContext);

        return $this->runContext($appContext);
    }

    public function prepareTestContext(?ServerRequestInterface $request = null): AppContext
    {
        $request ??= ServerRequestFactory::createFromGlobals();

        $appContext = $this->createContext($request);
        $appContext->disableDebugProfiler(true);
        static::$appContext = $appContext;

        $container = $appContext->getContainer();
        $request = $container->get(ServerRequestInterface::class);

        // @event
        $event = $this->emit(
            new BeforeRequestEvent(container: $container, request: $request)
        );

        // if ($handler) {
        //     $appContext->setController($handler);
        // }

        // @event
        $event = $appContext->emit(
            new BeforeRequestEvent(container: $container, request: $event->request)
        );

        return $appContext;
    }

    public function initializeDatabase(): void
    {
        if ($this->dbInitialized) {
            return;
        }

        $this->dbInitialized = true;

        $this->resetDatabase();
    }

    public function resetDatabase(): void
    {
        $db = self::$appContext->retrieve(DatabaseAdapter::class);

        $dbname = $db->driver->options->dbname;

        $db->driver->disconnectAll();
        $db->driver->options->dbname = null;

        $schemaManager = $db->getSchemaManager($dbname);
        $schemaManager->drop();
        $schemaManager->create();

        $db->driver->options->dbname = $dbname;
        $db->execute('USE ' . $dbname);

        $migrationService = TestApp::$instance->make(
            MigrationService::class,
            [
                'db' => $db,
            ]
        );

        $migrationService->migrate(
            WINDWALKER_MIGRATIONS,
            null,
            WINDWALKER_TEMP . '/db/test-mig.sql'
        );
    }

    // protected function terminating(Container $container): void
    // {
    //     parent::terminating($container);
    //
    //     $db = self::$appContext->retrieve(DatabaseAdapter::class);
    //     $db->driver->releaseKeptConnection();
    //     $db->disconnect();
    // }
}
