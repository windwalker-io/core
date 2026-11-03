<?php

declare(strict_types=1);

namespace Windwalker\Tests\Testing\Traits;

use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use Windwalker\Tests\TestApp;
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
    protected static DatabaseAdapter $db;
    protected static ORM $orm;

    /**
     * Resolve the ORM from the test container and call createTables().
     * Runs before the test class's own setUpBeforeClass().
     */
    #[BeforeClass]
    public static function bootDb(): void
    {
        TestApp::$instance->initializeDatabase();

        $db = TestApp::$instance->retrieve(DatabaseAdapter::class);

        static::$db = $db;
        static::$orm = $db->orm();

        static::prepareTablesBeforeClass();
    }

    /**
     * Truncate relevant tables before every test.
     * Runs before the test class's own setUp().
     */
    #[Before]
    public function prepareDatabaseBeforeTest(): void
    {
        $this->clearTables();
        $this->prepareFixtures();
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
    protected static function prepareTablesBeforeClass(): void {}

    /**
     * Override in the test class to truncate the tables it owns.
     */
    protected function clearTables(): void {}

    /**
     * Override in the test class to prepare fixtures.
     */
    protected function prepareFixtures(): void {}

    public static function truncateTables(...$tables): void
    {
        foreach ($tables as $table) {
            static::$orm->db->getTableManager($table)->truncate();
        }
    }
}
