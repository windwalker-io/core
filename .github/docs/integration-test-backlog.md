# Integration test backlog

## Purpose

Track code paths that are too heavy, environment-dependent, or
infrastructure-coupled to cover with a unit test, so the gap is visible and
not silently lost. This list is the single place to look for "what
integration test should I write next".

## Scope

List a code path only when a unit test genuinely can't exercise it, e.g.:

- needs a real host app, database, or optional package not installed in this
  package's dev dependencies;
- needs infrastructure (real view pipeline, real HTTP server, external
  service) a unit test can't reasonably fake;
- calls an internal (non-optional) service whose downstream behavior is
  heavy/complex enough that a stub would only prove "it was called", not
  that the real result is correct (`AppContext::renderView()`); or
- needs to obtain complex info from outside (e.g. the real request shape a
  CLI/HTTP server sends) where a hand-built double risks diverging from
  reality.

Do not list plain missing unit tests; only cases where integration-level
setup is genuinely required.

Optional-package branches cut both ways:

- A "package missing" guard unreachable only because the package *is*
  installed here doesn't need an entry — you can't deliberately uninstall it
  for one test, so skip it.
- A "package present" path doesn't need an entry either if an existing stub
  already faithfully implements the interface and the logic is a thin
  wrapper. Only list it when the real collaborators are too complex for a
  stub to stand in (`SimpleCspMiddleware`, `renderView()`).

## How to use this list

- When writing a test and you find a branch that can't be unit-tested for one
  of the reasons above, add an entry below instead of adding a skipped test
  or `@codeCoverageIgnore` annotation.
- When an integration test is added for an entry, remove that entry from this
  list in the same change.
- Keep entries short: class, path/branch, and why it needs integration-level
  setup.
- Group entries as a two-level list: the top-level bullet is the
  `src/Core` subdirectory (e.g. `Core/Application`, `Core/Middleware`)
  matching where the production file lives, with each entry nested
  underneath. Do not use headings for grouping.

## Pending items

- Core/Application
  - `Windwalker\Core\Application\AppContext::renderView()` — the
    `@codeCoverageIgnoreStart`/`End` branch that builds a real `View::class`
    graph (`RendererService`, `HtmlFrame`, `AssetService`, ...) can't be
    reasonably faked with stubs; needs a real host app rendering pipeline,
    e.g. via `TestWebAppFactory`. The same code path is also what blocks the
    `MaintenanceMiddleware` entry below.
- Core/Middleware
  - `Windwalker\Core\Middleware\MaintenanceMiddleware::process()` — the
    maintenance-view-rendering branch (no allowed IP, no valid secret, no
    redirect configured) calls `AppContext::make(BaseVM::class)` and
    `AppContext::renderView()`, which need a real view-rendering pipeline.
    Covered by `tests/Core/Middleware/MaintenanceMiddlewareTest.php` for
    every other branch.
  - `Windwalker\Core\Middleware\SimpleCspMiddleware::process()` — the CSP
    enabled/disabled building logic requires the optional
    `paragonie/csp-builder` package, which is not installed in this
    package's dev dependencies. Only the missing-package guard clause is
    currently covered by `tests/Core/Middleware/SimpleCspMiddlewareTest.php`.
- Core/Mailer
  - `Windwalker\Core\Mailer\MailBuilder::tryRenderMarkdown()` /
    `getMarkdownRenderer()` — the markdown-rendering branch requires the
    optional `erusev/parsedown` package, which is not installed in this
    package's dev dependencies. `tests/Core/Mailer/MailBuilderTest.php`
    covers every other branch; the "package present" path (actual
    `\Parsedown` rendering) needs `erusev/parsedown` installed to test.
- Core/Migration
  - `Windwalker\Core\Migration\MigrationService` — `tests/Core/Migration/MigrationServiceTest.php`
    covers `migrate()`/version-tracking orchestration against a SQLite
    `:memory:` `DatabaseAdapter` (tier 1), which is enough to prove the
    up/down ordering, ignore, and squash-coordination logic. It cannot prove
    that the DDL produced by the Schema API in real migration files actually
    succeeds on the dialects the host app targets in production (MySQL,
    PostgreSQL, SQL Server), since SQLite's lenient type affinity accepts
    column/index definitions a strict engine would reject. Needs a tier 4
    test with `DatabaseTestTrait` against `WINDWALKER_TEST_DB_DSN_*` running
    representative migrations end to end.
  - `Windwalker\Core\Migration\MigrationSquashBuilder::build()` /
    `Windwalker\Core\Migration\MigrationSquashService::generateMigrationCodes()`
    — these introspect a real table's columns/indexes/constraints
    (`TableManager::getColumns()/getIndexes()/getConstraints()`) and rebuild
    Schema-API code from the platform-specific metadata (column type names,
    auto-increment/primary detection, length/JSON "erratas"). That metadata
    shape comes from each driver's own `listColumns()`/`listIndexes()`
    implementation and differs meaningfully between MySQL, PostgreSQL, and
    SQLite, so a SQLite-backed test cannot validate `typeToMethod()` mapping
    or the auto-increment/JSON/length branches the way they behave against a
    real production engine. Needs a tier 4 test that introspects an actual
    MySQL/PostgreSQL table and asserts the generated migration code and
    version are correct.
- Core/Seed
  - `Windwalker\Core\Seed\SeedService::copySeedFile()` — delegates to
    `CodeGenerator::from()`, which globs real `.tpl` template files from disk
    and writes generated output through a real `ApplicationInterface`-backed
    `FileCollection`/event dealer. `CodeGenerator` is now constructor-injected
    (`#[Autowire]`), and `tests/Core/Seed/SeedServiceTest.php` covers the
    shallow, fully-mocked shape (a fake `CodeGenerator`/`FileCollection`
    verifies `from()`/`replaceTo()` are called with the right arguments, and
    `buildReplacementData()` is unit-tested in isolation as a pure method).
    That only proves wiring, not that a real `.tpl` template actually globs
    and renders correctly end to end — a stub `ApplicationInterface` can't
    fake real template lookup/rendering, so a tier 4 test still needs the
    same real-app setup as `MigrationService::copyMigrationFile()` (also
    untested for the same reason, and not yet backlogged there either).
    Follow-up: decide on a test strategy for this real-rendering gap — e.g.
    a tier 4 test using a real `ApplicationTraitStub`-compatible app plus
    actual seed `.tpl` fixtures under `tmp/`, or extracting template
    discovery/rendering out of `CodeGenerator` into something fakeable at a
    lower level — before closing this entry.
    `SeedService::import()`/`clear()`/`processIncluded()` are not listed
    here: they follow the exact same include-a-PHP-file-and-call-a-handler
    shape as `MigrationService::migrate()`/`getMigrations()`, which
    `tests/Core/Migration/MigrationServiceTest.php` already covers with real
    temp fixture files, `ApplicationTraitStub`, and an in-memory SQLite
    `DatabaseAdapter` (tier 1) — the same recipe applies directly to
    `SeedService` without any source change.
- Core/Runtime
  - `Windwalker\Core\Runtime\Runtime::forbidden()` / the blocking branch of
    `ipBlock()` — calls `header()` followed by `exit()`, a process-terminating
    side effect a unit test can't observe or recover from.
    `tests/Core/Runtime/RuntimeTest.php` covers every `shouldBlock()` branch
    that decides whether to block, plus the non-blocking path of `ipBlock()`.
- Core/Service
  - `Windwalker\Core\Service\ErrorService::exception()` — the catch branch
    that detects "infinity loop in exception & error handler" ends every
    path with `exit()`/`exit($msg)`, a process-terminating side effect a
    unit test can't observe or recover from.
    `tests/Core/Service/ErrorServiceTest.php` covers `handle()` (the
    delegate `exception()` calls before the unreachable-in-tests `exit()`)
    directly instead.
  - `Windwalker\Core\Service\ErrorService::down()` — the `true` branch
    (last error's type is `E_ERROR`) calls `$this->error(...)`, which throws
    an `ErrorException`; reaching it deterministically requires a real fatal
    error to populate `error_get_last()`, which can't be triggered safely
    inside a unit test process. `tests/Core/Service/ErrorServiceTest.php`
    covers the "no last error" and "last error present but not fatal"
    branches.
  - `Windwalker\Core\Service\ErrorService::registerShutdown()` — registers
    `down()` via `register_shutdown_function()`; the registration call
    itself is covered via `registerAlsoRegistersShutdownHandlerWhenRequested`,
    but whether the callback actually fires on script shutdown is a PHP
    engine guarantee with no synchronous, in-process way to observe it from
    a unit test.
- Core/Queue
  - `Windwalker\Core\Queue\QueueFactory::createSyncHandler()` — the `sync`
    driver's returned closure runs the job inline via a real `Invoker`/
    `ApplicationInterface::call()` and depends on process-level behavior
    (uncaught exceptions propagating synchronously); a unit test would only
    prove the closure was built, not that inline job execution behaves like
    production. `tests/Core/Queue/QueueFactoryTest.php` covers every other
    driver branch (`sqs`, default `QueueDriverInterface` resolution).
  - `Windwalker\Core\Queue\QueueFactory::sqsAdapter()` — the closure body
    constructs a real `Aws\Sqs\SqsClient` and `SqsQueueDriver`, which need
    the optional `aws/aws-sdk-php` package; it is not installed in this
    package's dev dependencies, so only the "package missing" guard is
    covered.
  - `Windwalker\Core\Queue\ScheduleWorker::run()` — the method's body is
    `$this->createWorker(...)->loop(...)`, i.e. `Worker::loop()`'s real
    `while` loop with signal handling and blocking queue polling. It has no
    deterministic single-iteration termination reachable from a unit test;
    `tests/Core/Queue/ScheduleWorkerTest.php` instead drives
    `listenToWorker()`'s event handlers directly via `Worker::emit()`, which
    covers all of the method's actual branching logic without running the
    loop.
