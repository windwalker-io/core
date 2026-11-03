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

    /**
     * Context a test class assigned to itself, usually in its own
     * `#[BeforeClass]` hook, when it needs to customize the request layer
     * (providers, bindings) instead of using {@see $sharedCtx}. Shared by
     * every test method in that class, the same way the static container in
     * `StaticContainerTestTrait` is.
     */
    public static AppContext $ctx;

    /**
     * Context created once at bootstrap by {@see prepareSharedContext()},
     * for tests that do not need to build their own and just want a ready
     * request-level context to retrieve services from.
     */
    public static AppContext $sharedCtx;

    protected bool $dbInitialized = false;

    public function executeServerEvent(RequestEvent $event): ResponseInterface
    {
        $appContext = $this->createContextFromServerEvent($event);

        $event->endHandler = fn() => $this->stopContext($appContext);

        return $this->runContext($appContext);
    }

    public static function createTestContext(?ServerRequestInterface $request = null): AppContext
    {
        $app = static::$instance ?? throw new \RuntimeException('TestApp instance not set.');

        $request ??= ServerRequestFactory::createFromGlobals();

        $appContext = $app->createContext($request);

        $appContext->disableDebugProfiler(true);

        $container = $appContext->getContainer();
        $request = $container->get(ServerRequestInterface::class);

        // @event
        $event = $app->emit(
            new BeforeRequestEvent(container: $container, request: $request)
        );

        // @event
        $appContext->emit(
            new BeforeRequestEvent(container: $container, request: $event->request)
        );

        return $appContext;
    }

    /**
     * Create and prepare the context shared by every test that does not
     * build its own, storing it in {@see $sharedCtx}. Called once from
     * `bootstrap.app.php`.
     */
    public function prepareSharedContext(?ServerRequestInterface $request = null): AppContext
    {
        $appContext = $this->createContext($request);

        static::$sharedCtx = $appContext;
        static::$ctx = $appContext;

        return $appContext;
    }

    public static function prepareTestContext(?ServerRequestInterface $request = null): AppContext
    {
        $appContext = static::$instance->createContext($request);

        static::$ctx = $appContext;

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
