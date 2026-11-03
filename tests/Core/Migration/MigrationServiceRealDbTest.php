<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Migration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Migration\MigrationService;
use Windwalker\Core\Testing\Stub\ApplicationTraitStub;
use Windwalker\Core\Testing\Traits\DatabaseTestTrait;

/**
 * Tier 4 integration test proving that the DDL produced by the Schema API
 * in a real migration file succeeds on a real DBMS (MySQL/PostgreSQL/
 * SQL Server), not just SQLite's lenient type affinity. Closes the
 * `Windwalker\Core\Migration\MigrationService` entry in
 * `.github/docs/integration-test-backlog.md`.
 *
 * Requires `WINDWALKER_TEST_DB_DSN` to point at a disposable database (see
 * `DatabaseTestTrait`); skipped otherwise. `MigrationServiceTest` already
 * covers migrate()/version-tracking orchestration against SQLite.
 */
#[CoversClass(MigrationService::class)]
class MigrationServiceRealDbTest extends TestCase
{
    use DatabaseTestTrait;

    #[Test]
    public function migrateRunsRepresentativeDdlAgainstRealDatabase(): void
    {
        $migrationsDir = dirname(__DIR__, 3) . '/tests/resources/migrations';

        $service = new MigrationService(new ApplicationTraitStub(), static::$db);

        $count = $service->migrate($migrationsDir, null);

        self::assertSame(1, $count);

        $table = static::$db->getTableManager('demo_items');

        self::assertTrue($table->exists());
        self::assertTrue($table->hasColumn('id'));
        self::assertTrue($table->hasColumn('slug'));
        self::assertTrue($table->hasColumn('price'));
        self::assertTrue($table->hasColumn('meta'));

        $hasUniqueSlug = false;

        foreach ($table->getConstraints() as $constraint) {
            if ($constraint->isUnique()) {
                $hasUniqueSlug = true;
            }
        }

        self::assertTrue($hasUniqueSlug, 'Unique constraint on slug should have been created.');

        self::assertSame(['20240101000001'], $service->getVersions());
    }
}
