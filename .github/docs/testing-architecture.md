# Testing Core by architecture layer

Use this document to decide how to test a change, based on the layer it
touches. The existing tests under [`tests/`](../../tests/) are the reference
implementations; read the nearest one before writing a new test. Architecture
background is in [`architecture.md`](architecture.md); app/DB prerequisites are
in [`host-application-and-testing.md`](host-application-and-testing.md); style
rules are in
[`../instructions/test-common.instructions.md`](../instructions/test-common.instructions.md)
and [`../instructions/test.instructions.md`](../instructions/test.instructions.md).

## Test tiers

Pick the lowest tier that covers the behavior.

| Tier | Needs | Use for | Reference |
|------|-------|---------|-----------|
| 1. Unit with doubles | nothing | one class with mocked collaborators (factories, services, form, managers) | [`tests/Core/Form/FormFactoryTest.php`](../../tests/Core/Form/FormFactoryTest.php) |
| 2. Light integration | no DB, no host app | a real `WebApplication` / `MicroApplication` booting, request context, middleware, routing, controller dispatch | [`tests/Core/Application/`](../../tests/Core/Application/) with [`TestWebAppFactory`](../../tests/Core/Application/Stub/TestWebAppFactory.php) |
| 3. App-backed | host app, `phpunit.app.dist.xml` | behavior that needs the host's providers, config, routes, or ORM | [`tests/TestApp.php`](../../tests/TestApp.php), [`tests/bootstrap.app.php`](../../tests/bootstrap.app.php), `StaticContainerTestTrait`, `ControllerTestTrait` |
| 4. Database | disposable DB, DSN or host DB config | ORM, migrations, seeders, query behavior | `DatabaseTestTrait` (see [`../instructions/test.instructions.md`](../instructions/test.instructions.md)) |

Tiers 3 and 4 depend on a host app; never make tier 1 or 2 tests depend on one.

## Tier 2: the standard recipe

`TestWebAppFactory::create()` builds an isolated app with its own
`Container` and `Config` (name, debug, secret only), so the whole pipeline
from [`architecture.md`](architecture.md#web-request-lifecycle) can be run
without `Runtime`, `www/`, or environment files:

```php
$app = TestWebAppFactory::create();
$ctx = $app->createContext(TestWebAppFactory::request('http://example.com/a?x=1'));
$res = $app->runContext($ctx, static fn(AppContext $app) => $app->input('x'));

self::assertSame('1', (string) $res->getBody());
```

- `createContext()` returns the request-level `AppContext`; each call has its
  own child container (asserted in `AppContextTest`).
- `runContext($ctx, $handler)` runs the middleware chain and dispatches
  `$handler` as the controller. `execute()` does the same from a request.
- Register middleware with `$app->addMiddleware()`; a closure middleware is a
  factory the container calls to obtain the real middleware.
- `MicroApplication::routing()` plus `->handler()` tests route matching and
  dispatch without view or config setup
  ([`MicroApplicationTest`](../../tests/Core/Application/MicroApplicationTest.php)).
- Use `TestWebAppFactory::request($uri, $method, $headers)` for PSR
  requests.

## What to test per layer

| Architecture layer | Assert | Tier | Existing tests |
|--------------------|--------|------|----------------|
| Runtime / paths | alias resolution (`@root`, ...) | 1 | `PathResolverTest` |
| Application boot and config | boot idempotent, config accessors, verbosity, secret, profiler toggle | 2 | `WebApplicationTest` |
| Container layers | each context has a distinct child container, not the app container | 2 | `AppContextTest` |
| `AppContext` request API | input, headers, method override, API-call detection, url vars, stage | 2 | `AppContextTest`, `AppRequestTraitTest` |
| Middleware | registration order, short-circuit, exceptions rethrown (debug) | 2 | `WebApplicationTest`, `MiddlewareRunnerTest` |
| Routing | route matched to handler and dispatched | 2 | `MicroApplicationTest` |
| Controller dispatch | injection of `AppContext`, restored state after `dispatchWithInput()` | 2/3 | `AppContextTest`, `ControllerTestTrait` |
| Enums and value types | cases and mappings | 1 | `AppEnumsTest`, `AppVerbosityTest` |
| Maintenance mode | config parsing and manager state | 1 | `tests/Core/Application/Offline/` |
| Forms | factory/renderer behavior with a mocked app | 1 | `tests/Core/Form/` |

Areas without tests yet (views and `View` events, `Renderer`/Edge, packages,
console commands, migration, seed, queue, mailer, session, security) should
follow the same tier choice: services with doubles in tier 1, anything that
needs the request pipeline in tier 2, and only ORM/DB behavior in tier 4.

## Testing request-scoped behavior

- Create a new context per test; never reuse an `AppContext` across tests, or
  one test's request, state, or messages leaks into another.
- To prove a service is request-scoped, create two contexts and assert the
  instances differ; app-scoped services must be the same.
- Inject the request through `createContext($request)` rather than touching
  globals.
- In tier 3, `TestApp::prepareTestContext()` sets the static
  `TestApp::$appContext`; `dispatchWithInput()` swaps the request and
  controller and restores them afterwards. Do not rely on state left by a
  previous test.
- Debug output: the test context disables the debug profiler
  (`disableDebugProfiler(true)`); tier 2 apps use `'debug' => false`. Set
  `'debug' => true` only to test debug-mode error propagation.

## Testing view models and controllers

A view model is a plain class with `#[ViewModel]` and a `prepare()` method (or
`#[ViewPrepare]`). Prefer testing it directly:

- Construct it with doubles for its dependencies (ORM, pagination factory) and
  call `prepare($appContext, $view)`; assert the returned data array and
  `HtmlFrame`/`#[ViewMetadata]` methods separately, since metadata methods take
  injected services and view data.
- Use a tier 2 context for input-dependent view models (for example a `page`
  query value) so `AppContext::input()` is real.
- Test controller classes through `dispatchController()` (tier 3) or
  `runContext($ctx, [Controller::class, 'task'])` (tier 2). Remember the default
  task mapping: GET `index`, POST/PUT/PATCH `save`, DELETE `delete`.
- Layout rendering and templates belong in app-backed tests; do not assert on
  full HTML in Core's unit tests.

## Fixtures

- Mandatory rules for where to put a new Stub/Mock class (check
  `tests/Testing/Stub/` first, when to promote a scoped one) are in
  [test.instructions.md's "Stubs and mocks" section](../instructions/test.instructions.md#stubs-and-mocks);
  read it before adding a new double.
- Keep tests free of host-specific names, routes, and config; the stub app
  supplies only what the behavior under test needs.

## Pitfalls found while closing coverage gaps in `Core/Application`

These are non-obvious failure modes hit while writing tier 2 tests against
`WebApplication`/`AppContext`. Check this list before debugging a DI or event
wiring error from scratch.

- **Share request-scoped overrides on the context container, not the root
  app container.** `createContext()`'s child container has its own
  resolution chain for things like `Navigator` → `EventEmitter` →
  `EventDispatcherRegistry`, gated by `providedIn`/container-level checks. A
  class shared on `$app->getContainer()` (root) does not override that chain;
  share it on `$ctx->getContainer()` (the container returned by
  `createContext()`/available via `AppContext::getContainer()`) instead:
  ```php
  $ctx = $app->createContext($request);
  $ctx->getContainer()->share(Router::class, new Router());
  $ctx->getContainer()->share(EventEmitter::class, new EventEmitter());
  ```
- **`config($name)` / `getParam($name, '.')` is a top-level dotted lookup.**
  `TestWebAppFactory::create()` wraps its config under `['app' => [...]]`,
  which only serves `app.*` paths (e.g. `config('app.debug')`). To make
  `config('middlewares')` resolve, load it as a separate top-level key:
  `$app->loadConfig(['middlewares' => [...]])`.
- **Registering event listeners before the app's first `boot()` call loses
  them.** `ApplicationTrait::prepareBoot()` replaces `$this->dispatcher` with
  a fresh `EventEmitter` the first time `boot()` runs. Any method that boots
  lazily (`createContext()`, `createContextFromServerEvent()`, ...) can
  trigger that reset. Always call `$app->boot()` explicitly before
  `$app->on(...)` in a test, not after.
- **Methods that call `runContext($appContext)` without a `$handler` argument**
  (`executeServerEvent()`, `runCliServerRequest()`) need a controller set some
  other way, since there is no router wired up in a tier 2 stub app. Register
  a `BeforeRequestEvent` listener that calls `setController()`:
  ```php
  $app->boot();
  $app->on(
      BeforeRequestEvent::class,
      fn ($event) => $event->container->get(AppContext::class)->setController(fn () => 'ok')
  );
  ```
- **Clover coverage is line-based, not branch-based.** One execution of a
  conditional line marks it covered regardless of which branch is taken; each
  branch's *body* lines still need a dedicated test. To cover a boolean
  condition that depends on a real environment function (e.g.
  `headers_sent()`, which is always `false` under the CLI test runner),
  override the wrapping method in a stub subclass with a settable toggle
  property instead of trying to fake the global function.
- **CLI-server-only dependencies are autowireable once `boot()` has run.**
  `CliServerClient` and `ErrorService` don't need manual container
  registration in tests: `ServiceAwareTrait::service()` falls back to
  `$container->createSharedObject(...)`, and their constructors only need
  `ApplicationInterface`/`Config`, both bound after `$app->boot()`.
- **`CliServerRuntime` writes to a real static `ConsoleOutput` (STDOUT).**
  Code paths that call it (`CliServerClient::logRequestInfo()`, the error
  branch of `runCliServerRequest()`) print real console noise during test
  runs; this is a harmless side effect, not a reason to avoid exercising
  those lines.
- **A minimal `ServerInterface` stub only needs 4 no-op methods**
  (`listen()`, `stop()`, and the inherited `EventListenableInterface`
  methods `subscribe()`/`on()`) to test `bootForServer()`.
- **On Windows, run `php-cs-fixer` through PHP directly, not the `bin/`
  bash wrapper.** `bash bin/php-cs-fixer ...` (and thus `composer cs-check`)
  can fail with `$'\r': command not found` when the repo's line endings are
  CRLF (`core.autocrlf=true`); use
  `php vendor/bin/php-cs-fixer check --diff --config=.php-cs-fixer.dist.php <files>`
  instead. Expect every file to show a CRLF-only diff in that case — that is
  a pre-existing, repo-wide artifact, not a real style violation to fix.
