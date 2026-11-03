<?php

declare(strict_types=1);

namespace Windwalker\Core\Testing\Database;

use Windwalker\Core\Application\PathResolver;
use Windwalker\Core\Factory\DatabaseServiceFactory;
use Windwalker\Core\Migration\MigrationService;
use Windwalker\Core\Seed\SeedService;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\Platform\AbstractPlatform;
use Windwalker\DI\Attributes\Service;
use Windwalker\Tests\TestApp;

#[Service]
class TestDatabaseFixtures
{
    public static bool $dbInitialized = false;

    public static function initialize(): void
    {
        if (static::$dbInitialized) {
            return;
        }

        static::$dbInitialized = true;

        static::reset();
    }

    public static function reset(?string $connection = null): void
    {
        $db = TestApp::$ctx->retrieve(DatabaseAdapter::class, tag: $connection);

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

    public static function createMigrationRunner(?string $connection = null): MigrationService
    {
        $db = TestApp::$ctx->retrieve(DatabaseAdapter::class, tag: $connection);

        return TestApp::$ctx->make(
            MigrationService::class,
            [
                'db' => $db,
            ],
        );
    }

    public static function migrate(?string $connection = null, string|bool $log = false): int
    {
        $factory = TestApp::$ctx->retrieve(DatabaseServiceFactory::class);
        $pathResolver = TestApp::$ctx->retrieve(PathResolver::class);

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

        // Do migrate
        return static::createMigrationRunner($connection)
            ->migrate(
                $pathResolver->resolve('@migrations'),
                null,
                $logFile,
            );
    }

    public static function createSeedRunner(?string $connection = null): SeedService
    {
        $db = TestApp::$ctx->retrieve(DatabaseAdapter::class, tag: $connection);

        return TestApp::$ctx->make(
            SeedService::class,
            [
                'db' => $db,
            ],
        );
    }

    public static function seeding(?string $connection = null): int
    {
        return static::createSeedRunner($connection)
            ->import(
                TestApp::$ctx->retrieve(PathResolver::class)->resolve('@seeders'),
            );
    }

    public static function resetAndMigrate(?string $connection = null, string|bool $log = false): void
    {
        static::reset($connection);
        static::migrate($connection, $log);
    }
}
