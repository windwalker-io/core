<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Migration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Migration\MigrationService;
use Windwalker\Core\Migration\MigrationSquashBuilder;
use Windwalker\Core\Migration\MigrationSquashService;
use Windwalker\Core\Testing\Stub\ApplicationTraitStub;
use Windwalker\Core\Testing\Traits\DatabaseTestTrait;
use Windwalker\Database\DatabaseAdapter;

/**
 * Tier 4 integration test proving that `MigrationSquashBuilder::build()` maps
 * a real table's introspected column/index/constraint metadata back into
 * Schema-API code correctly. SQLite's lenient type affinity would accept any
 * `typeToMethod()` mapping, length/JSON "errata" detection, or auto-increment
 * detection regardless of correctness, so this must run against the same
 * real DBMS the host app targets in production (see
 * `.github/docs/host-application-and-testing.md`), not SQLite. Closes the
 * `Windwalker\Core\Migration\MigrationSquashBuilder`/`MigrationSquashService`
 * entry in `.github/docs/integration-test-backlog.md`.
 *
 * Requires `WINDWALKER_TEST_DB_DSN` to point at a disposable MySQL database
 * (see `DatabaseTestTrait`); skipped otherwise.
 */
#[CoversClass(MigrationSquashBuilder::class)]
#[CoversClass(MigrationSquashService::class)]
class MigrationSquashServiceRealDbTest extends TestCase
{
    use DatabaseTestTrait;

    #[Test]
    public function generateMigrationCodesReproducesRealTableAsSchemaCode(): void
    {
        $migrationsDir = dirname(__DIR__, 3) . '/tests/resources/migrations';

        $app = new ApplicationTraitStub();
        $migrationService = new MigrationService($app, static::$db);
        $migrationService->migrate($migrationsDir, null);

        $app->getContainer()->share(DatabaseAdapter::class, static::$db);

        $squashService = new MigrationSquashService($app, $migrationService);

        $migrateCodes = $squashService->generateMigrationCodes(static::$db, one: true, group: true);

        self::assertArrayHasKey('main', $migrateCodes);
        self::assertCount(1, $migrateCodes['main']);

        [$upCode, $downCode] = $migrateCodes['main'][0];

        // Auto-increment bigint primary key.
        self::assertStringContainsString("\$schema->primaryBigint('id')", $upCode);

        // Plain varchar without a custom length.
        self::assertStringContainsString("\$schema->varchar('title')", $upCode);

        // Custom length is preserved.
        self::assertStringContainsString("\$schema->varchar('slug')->length(191)", $upCode);

        // Decimal precision/scale length is preserved.
        self::assertStringContainsString("\$schema->decimal('price')->length('20,6')", $upCode);

        // Nullable columns.
        self::assertStringContainsString("\$schema->integer('stock')->nullable(true)", $upCode);
        self::assertStringContainsString("\$schema->text('description')->nullable(true)", $upCode);

        // JSON errata detected from the real column type, not guessed.
        self::assertStringContainsString("\$schema->json('meta')->nullable(true)", $upCode);

        // Unique key and composite index, not conflated with the primary key.
        self::assertStringContainsString("\$schema->addUniqueKey('slug'", $upCode);
        self::assertStringContainsString("\$schema->addIndex(['stock', 'title']", $upCode);

        self::assertStringContainsString("\$this->dropTables('demo_items');", $downCode);
    }
}
