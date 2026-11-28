# Database testing

Guidelines for tests that access a real `DatabaseAdapter` or ORM, such as Repository CRUD methods
(`save`, `getItem`, `mustGetItem`, `delete`).

The project depends on `windwalker/core`, so its traits can be used directly in unicorn tests.

## Strategy selection

| Need | Use |
| --- | --- |
| Pure logic, no database needed | Mocks/stubs (`createStub()` / `createMock()`) |
| Real SQL/ORM behavior, no host app or DSN needed | `InMemoryDatabaseTestTrait` (SQLite `:memory:`) |
| Behavior specific to a dialect (MySQL/PostgreSQL) | Core's `DatabaseTestTrait` with a dedicated DSN |

SQLite is not fully equivalent to MySQL or PostgreSQL. When a test depends on dialect-specific SQL or DDL,
use an external DBMS.

## In-memory tests (pure logic)

Applicable to pure logical operations without special SQL features (such as MySQL's `ON DUPLICATE KEY UPDATE` or PostgreSQL's `RETURNING`), or schema-changing migrations.

Use `Windwalker\Core\Testing\Traits\InMemoryDatabaseTestTrait`. It opens a new `sqlite::memory:` connection before each test
and disconnects it afterward. Override `prepareInMemoryDatabaseSchema()` to create tables and insert rows.

```php
use Windwalker\Core\Testing\Traits\InMemoryDatabaseTestTrait;
use Windwalker\Database\DatabaseAdapter;

class ExampleRepositoryTest extends TestCase
{
    use InMemoryDatabaseTestTrait;

    protected function prepareInMemoryDatabaseSchema(DatabaseAdapter $db): void
    {
        $db->execute('CREATE TABLE examples (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT)');
    }
}
```

Use `setDb($this->db)` to inject the connection into the Repository and avoid the container lookup in `DatabaseRepositoryTrait::getDb()`.

## Real DBMS tests (must be explicitly enabled)

Applicable when tests generally need a real database connection and must test dialect-specific SQL behavior. The configured database may be dropped and recreated for each test case.

Rules:

- Use a dedicated, disposable database. Never use a shared, staging, or production database.
- Configure the DSN through `WINDWALKER_TEST_DB_DSN_*` in `phpunit.xml`. Leave it commented out by default
  so the test suite remains opt-in.
- Never run database test suites against the same database in parallel.
- Use environment variables for control; each agent can use a random ID suffix to create its own database and avoid conflicts between parallel tests.

Use `Windwalker\Core\Testing\Traits\DatabaseTestTrait`: it requires an `AbstractTestApp` subclass (such as `TestApp`), migrations, and the app ORM. It drops and recreates the configured database, so use it only with a dedicated disposable database.

## Mock-based tests

Mock-based tests should go in the `Unit` test suite. Use `createStub()` when only return values are needed; use `createMock()` with `expects()` to verify interactions. In PHPUnit 13 and later, `->with()` must follow `->expects(...)`, and `createStub()` does not support `->with()`.

## Related documentation

- [Testing](https://windwalker.io/documentation/framework/services/testing.md)
- [Database testing](https://windwalker.io/documentation/components/test/database.md)
- [Repository](https://windwalker.io/documentation/unicorn/services/repository.md)
- [Getting started with databases](https://windwalker.io/documentation/framework/services/database/getting-started.md)
- [Test component](https://windwalker.io/documentation/components/test/index.md)
