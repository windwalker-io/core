# Using Windwalker Core from a host application

## Package and application boundary

`windwalker/core` is a Composer framework package, not a particular application.
A consuming app may use any name and may live outside this repository; it
installs Core and supplies application-specific bootstrap, configuration,
routes, resources, and environment. The app's Composer autoloading maps its own
namespace (for example, `App\`) to its source directory, while Core maps
`Windwalker\` to Core's [`src/`](../../src/). Each package loads its own
bootstrap file.

Core code should use framework contracts and package APIs; app-specific
behavior and configuration belong in the consuming app. The Earth repository
is only one example of a host app, not a required test fixture or naming
convention. Earth-specific paths and architecture must not be assumed when
working in another workspace.

## Determine whether a host app is available

Before relying on application-specific context, inspect the current Core
package and its test configuration. If the task clearly identifies a consuming
app, or the workspace has an evident parent app that owns the Core checkout,
use that app as the integration context. Do not assume the app is named Earth
or is always located at a fixed relative path.

If no host app can be identified, treat Core as an independently checked-out
and independently tested package. Use Core's own [`composer.json`](../../composer.json),
[`phpunit.dist.xml`](../../phpunit.dist.xml), source, and test support; do not
search for or invent an application bootstrap, app config, routes, or
migrations. Run isolated Core tests directly. Add only a minimal test bootstrap
or fixture required by the specific behavior under test. If the behavior
genuinely requires a host app or external service, make that prerequisite
explicit and report it as unavailable rather than silently binding the test to
an unrelated project.

## Choose the PHPUnit configuration

When a host app is available, prefer its `phpunit.app.xml` configuration for
tests that need the app's Composer autoloader, bootstrap, environment, or
service configuration. Invoke PHPUnit with that explicit configuration
(for example, `phpunit -c /path/to/host-app/phpunit.app.xml`) rather than
letting it discover a package-local default. This applies when testing Core
from inside a host app; do not assume the host app is Earth or that it lives at
a fixed path.

If the host app has no `phpunit.app.xml`, use the package's
[`phpunit.app.dist.xml`](../../phpunit.app.dist.xml) as the host-app test
configuration when its bootstrap path and test setup fit the workspace. If
neither app-aware configuration is available or suitable, use Core's
[`phpunit.dist.xml`](../../phpunit.dist.xml) for standalone package tests, or
report the missing app-specific setup when the test requires it.

`phpunit.app.dist.xml` uses [`tests/bootstrap.app.php`](../../tests/bootstrap.app.php),
which loads the host autoloader (root from `WINDWALKER_TEST_APP_ROOT`, default
`dirname(__DIR__, 4)`), boots the runtime, and sets
`Windwalker\Tests\TestApp::$instance`. `TestApp::resetDatabase()` DROPS the
configured database, so point `DATABASE_NAME` (via an `APP_ENV` `.env.*` file)
at a disposable database before running DB tests.

## What the host app supplies at runtime

When Core functionality depends on a fully booted application, the host app
must provide the following integration points:

- **Application root and paths:** when the scenario needs them, define the
  application root and standard paths for configuration, routes, resources,
  views, public files, logs, cache, and temporary files. For example, an app
  may define `WINDWALKER_*` constants, load environment files, and derive
  debug/cache/temp settings in its own bootstrap.
- **Environment and configuration:** load the app environment and assemble
  application, dependency-injection, and installed-package configuration.
  The location and format are owned by the app.
- **Runtime and application boot:** boot Core's `Runtime` with the host
  application's root and work directories, then construct and boot the
  appropriate Core application with its configured container. The host app
  owns this lifecycle; Core's Composer autoload alone does not boot an app.
- **Routes and app features:** register the routes, middleware, controllers,
  view models, and package routes needed by the integration scenario.
- **Writable runtime directories:** ensure any directories used by the
  scenario (such as temporary files, logs, or cache) exist and are writable.
- **External dependencies:** configure services exercised by the scenario,
  such as a database, Redis, or a queue backend. These are not required for
  tests that do not use them.

An integration test should state which host capabilities it requires. Do not
make unit tests boot a complete consuming app when a focused test double or
isolated container can cover the behavior.

## Testing Core with an application context

Core's test support includes [`tests/TestApp.php`](../../tests/TestApp.php). It
extends `WebApplication` and creates request contexts; database-backed test
support uses
[`tests/Testing/Traits/DatabaseTestTrait.php`](../../tests/Testing/Traits/DatabaseTestTrait.php)
to obtain the configured
`DatabaseAdapter` and ORM. A database test bootstrap must create the app
container, configure the database provider, initialize `TestApp::$instance`,
and prepare `TestApp::$ctx` (via `prepareSharedContext()`) before database-backed traits are used.
Composer autoloading alone does not provide this application state. This test
app can be configured by a consuming app when one is available, or by Core's
own isolated test bootstrap when Core is tested independently.

For database integration tests:

- Configure a dedicated disposable test database using the DSN constants
  documented in [`phpunit.dist.xml`](../../phpunit.dist.xml)
  (`WINDWALKER_TEST_DB_DSN_*`) and provide matching database configuration in
  the test app or standalone test fixture.
- Provide `WINDWALKER_MIGRATIONS` and `WINDWALKER_TEMP` when using
  `TestApp::initializeDatabase()`.
- `TestApp::resetDatabase()` drops and recreates the selected database before
  running its migrations. The configured database account therefore needs
  permission to drop and create the test schema. Never point it at a developer,
  shared, staging, or production database.
- `resetDatabase()` only issues the MySQL-specific `USE <dbname>` statement
  when the resolved platform is MySQL, so pointing the test DSN at a
  different platform will not fail on that statement. This is a defensive
  guard only: tier 4 is still intended to run against the same real DBMS the
  host app targets in production (migrations may contain dialect-specific
  DDL), not as a generic real-DB/SQLite switch. For lightweight tests that
  need a real SQLite-backed `DatabaseAdapter` without a host app, use
  `InMemoryDatabaseTestTrait` instead (see
  [`test.instructions.md`](../instructions/test.instructions.md)).
- If the test depends on app-provided migrations, providers, or services,
  install and configure those in the identified host app. Without a host app,
  provide only the explicit fixtures needed by the test.

Tests for optional external services should be opt-in and require their
service-specific configuration, as indicated by the commented flags in
[`phpunit.dist.xml`](../../phpunit.dist.xml). Prefer isolated unit or component
tests unless app boot, routes, database migrations, or real external services
are part of the behavior under test.
