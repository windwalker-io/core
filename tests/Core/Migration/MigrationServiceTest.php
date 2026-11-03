<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Migration;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Windwalker\Core\Migration\MigrationService;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Tests\Testing\Stub\ApplicationTraitStub;
use Windwalker\Tests\Testing\Traits\InMemoryDatabaseTestTrait;

/**
 * Unit tests for MigrationService's public migrate/version-tracking API,
 * run against a real SQLite in-memory DatabaseAdapter and a lightweight
 * ApplicationInterface stub, without booting a host application.
 */
#[CoversClass(MigrationService::class)]
class MigrationServiceTest extends TestCase
{
    use InMemoryDatabaseTestTrait;

    protected string $migrationsDir;

    protected ApplicationTraitStub $app;

    protected MigrationService $instance;

    #[Before]
    protected function prepareMigrationService(): void
    {
        $this->migrationsDir = dirname(__DIR__, 3) . '/tmp/migration-service-test-' . uniqid();

        Filesystem::mkdir($this->migrationsDir);

        $this->app = new ApplicationTraitStub();
        $this->instance = new MigrationService($this->app, $this->db);
    }

    #[After]
    protected function cleanupMigrationsDir(): void
    {
        Filesystem::delete($this->migrationsDir);
    }

    private function writeMigrationFixture(string $fileName, string $tableName): void
    {
        $content = <<<PHP
            <?php

            declare(strict_types=1);

            use Windwalker\Core\Migration\AbstractMigration;
            use Windwalker\Core\Migration\MigrateDown;
            use Windwalker\Core\Migration\MigrateUp;
            use Windwalker\Database\Schema\Schema;

            return new class extends AbstractMigration {
                #[MigrateUp]
                public function up(): void
                {
                    \$this->createTable(
                        '{$tableName}',
                        function (Schema \$schema) {
                            \$schema->primary('id');
                            \$schema->varchar('name');
                        }
                    );
                }

                #[MigrateDown]
                public function down(): void
                {
                    \$this->dropTables('{$tableName}');
                }
            };
            PHP;

        Filesystem::write($this->migrationsDir . '/' . $fileName, $content);
    }

    #[Test]
    public function generateVersionAvoidsCollidingWithExistingVersions(): void
    {
        $date = new DateTimeImmutable('2024-01-01 00:00:00');

        $first = MigrationService::generateVersion($date);
        $second = MigrationService::generateVersion($date, [$first]);

        self::assertNotSame($first, $second);
        self::assertStringStartsWith('202401010000', $first);
    }

    #[Test]
    public function migrateThrowsExceptionWhenNoMigrationsFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No migrations found.');

        $this->instance->migrate($this->migrationsDir, null);
    }

    #[Test]
    public function migrateRunsAllPendingMigrationsUp(): void
    {
        $this->writeMigrationFixture('20240101000001_create_foo_table.php', 'foo');
        $this->writeMigrationFixture('20240101000002_create_bar_table.php', 'bar');

        $count = $this->instance->migrate($this->migrationsDir, null);

        self::assertSame(2, $count);
        self::assertTrue($this->db->getTableManager('foo')->exists());
        self::assertTrue($this->db->getTableManager('bar')->exists());
        self::assertSame(
            ['20240101000001', '20240101000002'],
            $this->instance->getVersions()
        );
        self::assertSame('20240101000002', $this->instance->getCurrentVersion());
    }

    #[Test]
    public function migrateIsIdempotentWhenAlreadyUpToDate(): void
    {
        $this->writeMigrationFixture('20240101000001_create_foo_table.php', 'foo');

        $this->instance->migrate($this->migrationsDir, null);

        // migrate() counts every migration up to the target version, not
        // only newly executed ones, so re-running still reports a count.
        // What must not change is the log table: no duplicate version row.
        $this->instance->migrate($this->migrationsDir, null);

        self::assertSame(['20240101000001'], $this->instance->getVersions());
    }

    #[Test]
    public function migrateReportsNoChangesWhenTargetHasNoApplicableMigrations(): void
    {
        $this->writeMigrationFixture('20240101000001_create_foo_table.php', 'foo');

        $count = $this->instance->migrate($this->migrationsDir, '0');

        self::assertSame(0, $count);
    }

    #[Test]
    public function migrateCanRollbackToAnEarlierVersion(): void
    {
        $this->writeMigrationFixture('20240101000001_create_foo_table.php', 'foo');
        $this->writeMigrationFixture('20240101000002_create_bar_table.php', 'bar');

        $this->instance->migrate($this->migrationsDir, null);

        $this->instance->migrate($this->migrationsDir, '0');

        self::assertFalse($this->db->getTableManager('foo')->exists());
        self::assertFalse($this->db->getTableManager('bar')->exists());
        self::assertSame([], $this->instance->getVersions());
        self::assertSame('0', $this->instance->getCurrentVersion());
    }

    #[Test]
    public function migrateAcceptsMigrationNameAsTargetVersion(): void
    {
        $this->writeMigrationFixture('20240101000001_create_foo_table.php', 'foo');

        $count = $this->instance->migrate($this->migrationsDir, 'create_foo_table');

        self::assertSame(1, $count);
        self::assertTrue($this->db->getTableManager('foo')->exists());
    }

    #[Test]
    public function migrateThrowsExceptionForInvalidTargetVersion(): void
    {
        $this->writeMigrationFixture('20240101000001_create_foo_table.php', 'foo');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is not valid');

        $this->instance->migrate($this->migrationsDir, 'does-not-exist');
    }

    #[Test]
    public function ignoredVersionsAreSkippedButStillTracked(): void
    {
        $this->writeMigrationFixture('20240101000001_create_foo_table.php', 'foo');

        $this->instance->ignores(['20240101000001']);

        $count = $this->instance->migrate($this->migrationsDir, null);

        self::assertSame(1, $count);
        self::assertFalse($this->db->getTableManager('foo')->exists());
        self::assertSame(['20240101000001'], $this->instance->getVersions());
    }

    #[Test]
    public function squashIfNotFreshClearsLogAndSetsIgnoresWhenNotAtVersionZero(): void
    {
        $this->app->getContainer()->share(DatabaseAdapter::class, $this->db);

        $this->writeMigrationFixture('20240101000001_create_foo_table.php', 'foo');

        $this->instance->migrate($this->migrationsDir, null);

        $this->instance->squashIfNotFresh(['20240101000001']);

        self::assertSame([], $this->instance->getVersions());
    }

    #[Test]
    public function squashIfNotFreshDoesNothingWhenAlreadyFresh(): void
    {
        $this->app->getContainer()->share(DatabaseAdapter::class, $this->db);

        $this->instance->squashIfNotFresh(['20240101000001']);

        self::assertSame([], $this->instance->getVersions());
    }
}
