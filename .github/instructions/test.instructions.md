---
applyTo: "tests/**/*.php"
---

# Core testing instructions

Apply the [shared PHP testing rules](test.instructions.md) together
with the Core-specific conventions below.

## Test placement

- Mirror the object's path under `src/` in `tests/`, and name its test class
  and file `<ClassName>Test`.
  For example, `src/Core/Router/Navigator.php` belongs with
  `tests/Core/Router/NavigatorTest.php`.
- Use the `Windwalker\Tests\` namespace followed by the mirrored directory
  path, consistent with the development autoload mapping in `composer.json`.

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
- [`ControllerTestTrait.php`](../../tests/Testing/Traits/ControllerTestTrait.php):
  use this as a reference when executing controllers.
  Its `dispatchController()` delegates to `TestApp::$appContext` and accepts a
  controller, input data or request, and explicit method arguments. Prepare
  the app context before dispatching.

## Execution context

- Read [host application and testing guidance](../docs/host-application-and-testing.md)
  before configuring app-backed integration tests.
- When a host app is detected, prefer `phpunit.app.xml` and pass it explicitly
  with `-c`, following the documented configuration fallback order.
- If no host app is available, treat Core as standalone. Use isolated tests
  or a minimal test fixture; do not assume an app name, location, or bootstrap.
