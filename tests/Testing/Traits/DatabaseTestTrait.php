<?php

declare(strict_types=1);

namespace Windwalker\Tests\Testing\Traits;

use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use Tests\TestApp;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\ORM\ORM;

/**
 * Provides a real-DB ORM instance and common helpers for test cases.
 *
 * Lifecycle hooks use PHPUnit attributes so they compose safely with any
 * setUpBeforeClass / setUp the consuming test class defines:
 *   - #[BeforeClass] bootOrm()        — runs before setUpBeforeClass()
 *   - #[Before]      truncateBeforeTest() — runs before setUp()
 */
trait DatabaseTestTrait
{
    protected static ORM $orm;

    /**
     * Resolve the ORM from the test container and call createTables().
     * Runs before the test class's own setUpBeforeClass().
     */
    #[BeforeClass]
    public static function bootOrm(): void
    {
        TestApp::$instance->initializeDatabase();

        $db = TestApp::$instance->retrieve(DatabaseAdapter::class);

        static::$orm = $db->orm();

        static::prepareTables();
    }

    /**
     * Truncate relevant tables before every test.
     * Runs before the test class's own setUp().
     */
    #[Before]
    public function truncateBeforeTest(): void
    {
        static::clearTables();
    }

    #[AfterClass]
    public static function releaseDatabase(): void
    {
        static::$orm->db->getDriver()->releaseKeptConnection();
        static::$orm->db->disconnect();
    }

    /**
     * Override in the test class to create tables (usually a no-op when
     * migrations handle the schema).
     */
    protected static function prepareTables(): void {}

    /**
     * Override in the test class to truncate the tables it owns.
     */
    protected static function clearTables(): void {}

    public static function truncateTables(...$tables): void
    {
        foreach ($tables as $table) {
            static::$orm->db->getTableManager($table)->truncate();
        }
    }
}
