# Windwalker app testing

Host app tests are an integration environment from the start: configuration and providers are already set up. You do not need to specify `phpunit.app.xml` or reassemble providers.

## Automatic reset (applies to all tiers)

- When a TestCase that needs a database starts: refresh migrations.
  - Migrations run only the first time a database-dependent test is reached.
- Before each test method: truncate tables.
- Tests do not need to create tables or clear data themselves, and must not depend on data left by a previous test.

## Tiers

Choose the lowest tier that covers the behavior. Tiers 1 and 2 may both use a DB.

| Tier | Description | Use for | DB |
| --- | --- | --- | --- |
| 1 Unit | mock dependent services | one class + test doubles | as needed |
| 2 Light integration | real services and DI in the app | request context, middleware, routing, controller dispatch | as needed |
| 3 DB: memory | SQLite `:memory:` | pure SQL/ORM logic without dialect-specific features | memory |
| 4 DB: real | real DBMS | dialect behavior, migrations, seeders | real |

- For tier 3 and 4 rules and traits, see `database-testing.md`.
- For tier 1 test doubles, see `phpunit-testing.md`.

## Helpers (`src/Core/Testing/`)

- `AbstractTestApp`: app test base class; `tests/TestApp.php` is a subclass.
- `Traits/ServiceAwareTestTrait`: one child container per test class; provides `make()`, `retrieve()`, and `call()`; requires `AbstractTestApp::$ctx` (or `$sharedCtx`) to be prepared first; does not boot the app.
- `Traits/ControllerTestTrait`: `dispatchController()` accepts a controller, input or request, and method arguments; requires the app context to be prepared first.
- `Traits/InMemoryDatabaseTestTrait`, `Traits/DatabaseTestTrait`: see `database-testing.md`.

## Assertions by tier

| Subject | Assertions | Tier |
| --- | --- | --- |
| Enum / value type | cases and mappings | 1 |
| Service / Factory | return values, interactions with collaborators | 1 |
| `AppContext` request API | input, headers, method override, API detection, URL vars, stage | 2 |
| Middleware | order, short-circuit, exception rethrowing in debug mode | 2 |
| Routing | route matching and dispatch to handler | 2 |
| Controller dispatch | `AppContext` injection, state restored after `dispatchWithInput()` | 2 |
| Repository / ORM | CRUD, query results | 3 / 4 |
| Migration / Seeder | schema and data | 4 |

## Request-scoped behavior

- Create a new context for each test; do not reuse an `AppContext` across tests.
- To verify request scope, create two contexts and assert their instances differ; app-scoped services should be the same.
- Inject the request with `createContext($request)`; do not touch globals.
- `AbstractTestApp::prepareSharedContext()` sets `$sharedCtx` / `$ctx`; `dispatchWithInput()` swaps and restores the request/controller.
- The test context disables the debug profiler; set `'debug' => true` only when testing error propagation in debug mode.
- Services marked `#[Isolation]` are rebuilt for each context; use this to assert the two contexts have different instances.

## View models and controllers

- Test a view model directly: construct it with doubles, call `prepare($appContext, $view)`, and assert returned data separately from `HtmlFrame` / `#[ViewMetadata]`.
- For a view model that depends on input, use a tier 2 context.
- Controllers: use `dispatchController()` or `runContext($ctx, [Controller::class, 'task'])`.
- Default tasks: GET `index`, POST/PUT/PATCH `save`, DELETE `delete`.
- Do not assert full HTML in unit tests; test layouts and templates at tier 2.

## Fixtures

- Do not use names, routes, or config specific to other projects in tests.
