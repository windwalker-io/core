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
  that the real result is correct (`SimpleCspMiddleware::process()`); or
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

- Core/Database
  - `SqlsrvExporter` SQL/native CLI paths — real round trips still need
    mssql-scripter and SQLPackage installed. `LiveExporterIntegration.php`
    covers PHP fallback against SQL Server, including identity state,
    computed/rowversion columns, defaults, indexes, binary data and foreign keys.
  - `OracleExporter` SQLcl, Data Pump and PHP paths — require SQLcl, an Oracle
    directory-object/local-path mapping, and a PHP OCI driver respectively.
    `LiveExporterIntegration.php` covers legacy exp/imp round trips; its
    PostgreSQL cases cover plain/custom pg_dump and PHP. Advanced user-defined
    types, large LOBs and vendor-specific schema features need additional
    server fixtures.

- Core/Middleware
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
