<?php

declare(strict_types=1);

namespace Windwalker\Core\Testing\Traits;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\DatabaseFactory;

/**
 * Provides a real, disposable `DatabaseAdapter` backed by SQLite `:memory:`
 * for tier 1 unit tests that need genuine query/statement behavior without a
 * host application, migrations, or a configured DSN.
 *
 * This is distinct from the app-backed `DatabaseTestTrait` (tier 4), which
 * boots the host app's ORM and migrations against a real, configured
 * database. Use this trait when the class under test only needs a working
 * `DatabaseAdapter` and owns its own minimal schema.
 *
 * Lifecycle hooks use PHPUnit attributes so they compose safely with any
 * `#[Before]` / `#[After]` the consuming test class defines:
 *   - #[Before] creates a fresh in-memory connection before every test
 *   - #[After]  disconnects after every test
 */
trait InMemoryDatabaseTestTrait
{
    protected DatabaseAdapter $db;

    #[Before]
    protected function prepareInMemoryDatabase(): void
    {
        $this->db = new DatabaseFactory()->create('pdo_sqlite', ['dsn' => 'sqlite::memory:']);

        $this->prepareInMemoryDatabaseSchema($this->db);
    }

    #[After]
    protected function closeInMemoryDatabase(): void
    {
        $this->db->disconnect();
    }

    /**
     * Override in the test class to create tables and seed rows.
     * Runs right after the connection is created, before every test.
     */
    protected function prepareInMemoryDatabaseSchema(DatabaseAdapter $db): void
    {
        //
    }
}
