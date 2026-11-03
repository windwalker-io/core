# Integration test backlog

## Purpose

Track code paths that are too heavy, environment-dependent, or
infrastructure-coupled to cover with a unit test, so the gap is visible and
not silently lost. This list is the single place to look for "what
integration test should I write next".

## Scope

Any production code path discovered while writing or reviewing tests that:

- needs a real host application, database, or optional dependency not
  installed in this package's dev environment, or
- needs infrastructure (real view rendering pipeline, real HTTP server,
  external service) that a unit test cannot reasonably fake.

Do not list plain missing unit tests here; only list cases where a full
integration test is the appropriate next step.

## How to use this list

- When writing a test and you find a branch that can't be unit-tested for one
  of the reasons above, add an entry below instead of adding a skipped test
  or `@codeCoverageIgnore` annotation.
- When an integration test is added for an entry, remove that entry from this
  list in the same change.
- Keep entries short: class, path/branch, and why it needs integration-level
  setup.

## Pending items

- `Windwalker\Core\Middleware\MaintenanceMiddleware::process()` — the
  maintenance-view-rendering branch (no allowed IP, no valid secret, no
  redirect configured) calls `AppContext::make(BaseVM::class)` and
  `AppContext::renderView()`, which need a real view-rendering pipeline.
  Covered by `tests/Core/Middleware/MaintenanceMiddlewareTest.php` for every
  other branch.
- `Windwalker\Core\Middleware\SimpleCspMiddleware::process()` — the CSP
  enabled/disabled building logic requires the optional
  `paragonie/csp-builder` package, which is not installed in this package's
  dev dependencies. Only the missing-package guard clause is currently
  covered by `tests/Core/Middleware/SimpleCspMiddlewareTest.php`.
