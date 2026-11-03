<?php

declare(strict_types=1);

namespace Windwalker\Tests;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\PathResolver;
use Windwalker\Core\Application\WebApplication;
use Windwalker\Core\Events\Web\BeforeRequestEvent;
use Windwalker\Core\Factory\DatabaseServiceFactory;
use Windwalker\Core\Migration\MigrationService;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\Platform\AbstractPlatform;
use Windwalker\Http\Event\RequestEvent;
use Windwalker\Http\Factory\ServerRequestFactory;

class TestApp extends WebApplication
{
    public static self $instance;

    public static AppContext $ctx;

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
        static::$ctx = $appContext;

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

        static::resetDatabase();
    }

    public static function resetDatabase(?string $connection = null, string|bool $log = false): void
    {
        $db = self::$ctx->retrieve(DatabaseAdapter::class, tag: $connection);

        $dbname = $db->driver->options->dbname;

        $db->driver->disconnectAll();
        $db->driver->options->dbname = null;

        $schemaManager = $db->getSchemaManager($dbname);
        $schemaManager->drop();
        $schemaManager->create();

        $db->driver->options->dbname = $dbname;

        // `USE <dbname>` is MySQL-specific raw SQL and is not supported by
        // other drivers (e.g. SQLite has no server-side schema switching).
        // Skip it for non-MySQL platforms so this reset logic does not break
        // if the test DSN ever points to a different driver.
        if ($db->getPlatform()->getName() === AbstractPlatform::MYSQL) {
            $db->execute('USE ' . $dbname);
        }
    }

    public static function migrate(?string $connection = null, string|bool $log = false): int
    {
        $factory = self::$ctx->retrieve(DatabaseServiceFactory::class);
        $db = self::$ctx->retrieve(DatabaseAdapter::class, tag: $connection);
        $pathResolver = self::$ctx->retrieve(PathResolver::class);

        // Logging
        $logName = $connection ?? $factory->getDefaultName();

        $logFile = null;

        if ($log) {
            if ($log === true) {
                $logFile = $pathResolver->resolve('@temp') . "/db/test-mig-{$logName}.sql";
            } else {
                $logFile = $log;
            }
        }

        $migrationService = static::$instance->make(
            MigrationService::class,
            [
                'db' => $db,
            ]
        );

        // Do migrate
        return $migrationService->migrate(
            $pathResolver->resolve('@migrations'),
            null,
            $logFile
        );
    }



    public static function resetDatabaseAndMigrate(?string $connection = null, string|bool $log = false): void
    {
        static::resetDatabase($connection);
        static::migrate($connection, $log);
    }

    // protected function terminating(Container $container): void
    // {
    //     parent::terminating($container);
    //
    //     $db = self::$appContext->retrieve(DatabaseAdapter::class, tag: $connection);
    //     $db->driver->releaseKeptConnection();
    //     $db->disconnect();
    // }
}
