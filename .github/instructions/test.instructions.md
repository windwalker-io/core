---
applyTo: "tests/**/*.php"
---

# Core testing instructions

Apply the [shared PHP testing rules](test-common.instructions.md) together
with the Core-specific conventions below. To choose a test tier (unit, light
integration with `TestWebAppFactory`, app-backed, database), see
[Testing Core by architecture layer](../docs/testing-architecture.md).

## Test placement

- Mirror the object's path under `src/` in `tests/`, and name its test class
  and file `<ClassName>Test`.
  For example, `src/Core/Router/Navigator.php` belongs with
  `tests/Core/Router/NavigatorTest.php`.
- Use the `Windwalker\Tests\` namespace followed by the mirrored directory
  path, consistent with the development autoload mapping in `composer.json`.

## Stubs and mocks

- Before writing a new Stub or Mock class, check
  [`tests/Testing/Stub/`](../../tests/Testing/Stub/) for an existing double
  that already does the job. Do not duplicate one that already exists there.
- If the double you are about to write has no dependency on the specific
  feature/class under test (e.g. a generic PSR-3 logger spy, a PSR-15
  passthrough middleware, a PSR-7/HTTP server no-op), add it to
  `tests/Testing/Stub/` instead of a feature-local `Stub/` directory, so
  other test suites can reuse it. This repo uses a single `Stub/` naming
  convention for both stubs and mocks; there is no separate `Mock/`
  directory.
- If the double is tied to one feature/class (constructor shape, specific
  behavior only that code exercises), keep it in that area's own `Stub/`
  directory next to the tests that use it (see "Fixtures" in
  [testing-architecture.md](../docs/testing-architecture.md)), with a
  docblock stating what depends on it and when it can be removed. Promote it
  to `tests/Testing/Stub/` once a second, unrelated test needs the same
  double.
- Current reusable entries in `tests/Testing/Stub/` (update this list when
  adding or removing one):
  - `SpyLogger`: PSR-3 logger that records every `log()` call for assertions.
  - `PassThroughMiddleware`: PSR-15 middleware that tags the request with a
    `passed` attribute and delegates; for middleware pipeline tests.
  - `FakeHttpServer`: no-op `Windwalker\Http\Server\ServerInterface`
    implementation; for tests that need to boot against a fake server.

## Test helpers

- [`StaticContainerTestTrait.php`](../../tests/Testing/Traits/StaticContainerTestTrait.php):
  use this as a reference when dependency injection is needed.
  It creates a child container for each test class and exposes `make()`,
  `retrieve()`, and `call()`. Prepare `TestApp::$appContext` first; this trait
  does not bootstrap the application.
- [`DatabaseTestTrait.php`](../../../framework/packages/test/src/Traits/DatabaseTestTrait.php):
  use `Windwalker\Test\Traits\DatabaseTestTrait` from `windwalker/test` for
  database tests. This link points to its source in this workspace.
  In other installations, resolve that trait from the installed package
  rather than assuming a sibling framework checkout. Implement
  `setupDatabase()` and use `createDatabase()` with explicit parameters or
  `WINDWALKER_TEST_DB_DSN_*` configuration. The trait skips tests when no DSN
  is configured and disconnects after the test class.
- [Core's DatabaseTestTrait.php](../../tests/Testing/Traits/DatabaseTestTrait.php):
  this is distinct from the framework trait above. It uses `TestApp`,
  migrations, and the app's ORM and can drop and recreate the configured
  database. Use it only for tests requiring that lifecycle and a dedicated
  disposable database.
- [`InMemoryDatabaseTestTrait.php`](../../tests/Testing/Traits/InMemoryDatabaseTestTrait.php):
  use this for tier 1 unit tests that need a real `DatabaseAdapter` (actual
  query/statement behavior) without a host app, migrations, or a configured
  DSN. It opens a fresh SQLite `:memory:` connection before every test and
  disconnects after. Override `prepareInMemoryDatabaseSchema(DatabaseAdapter $db)`
  in the test class to create tables and seed rows.
- [`ControllerTestTrait.php`](../../tests/Testing/Traits/ControllerTestTrait.php):
  use this as a reference when executing controllers.
  Its `dispatchController()` delegates to `TestApp::$appContext` and accepts a
  controller, input data or request, and explicit method arguments. Prepare
  the app context before dispatching.

## Known pitfalls

- Before writing a tier 2 (`TestWebAppFactory`) test for `WebApplication`/
  `AppContext`/event or DI wiring, check
  [testing-architecture.md's pitfalls list](../docs/testing-architecture.md#pitfalls-found-while-closing-coverage-gaps-in-coreapplication)
  for known gotchas (request-container overrides, `config()` path resolution,
  event listener timing, coverage branch semantics) before debugging from
  scratch.

## Execution context

- Read [host application and testing guidance](../docs/host-application-and-testing.md)
  before configuring app-backed integration tests.
- When a host app is detected, prefer `phpunit.app.xml` and pass it explicitly
  with `-c`, following the documented configuration fallback order.
- If no host app is available, treat Core as standalone. Use isolated tests
  or a minimal test fixture; do not assume an app name, location, or bootstrap.
