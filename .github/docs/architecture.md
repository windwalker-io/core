# Windwalker Core architecture

This document explains how the `windwalker/core` package is organized and how
a request or console command moves through it. It is a reference for working
inside Core; for how an application boots Core and for test prerequisites, see
[`host-application-and-testing.md`](host-application-and-testing.md). The
mandatory rules derived from this architecture are in
[`../instructions/architecture.instructions.md`](../instructions/architecture.instructions.md).

## Package layout

Namespace `Windwalker\` maps to [`src/`](../../src/); tests use
`Windwalker\Tests\` from [`tests/`](../../tests/).

- [`src/Core/`](../../src/Core/): the framework implementation. One directory per
  capability, see [Capability areas](#capability-areas).
- [`src/Console/`](../../src/Console/): console IO and command primitives
  (`CommandWrapper`, `IO`, completion, questions) built on Symfony Console.
- [`src/WebSocket/`](../../src/WebSocket/): WebSocket application, router,
  parser, and Swoole support.
- [`src/Debugger/`](../../src/Debugger/): the debugger package (itself an
  `AbstractPackage`), with its own module, services, and subscribers.
- [`src/bootstrap.php`](../../src/bootstrap.php) and
  [`src/functions.php`](../../src/functions.php): autoloaded global helpers.
- [`etc/`](../../etc/): Core's own config fragments (`attributes.php` registers
  the attribute handlers; `debugger.config.php` is the debugger config).
- [`routes/`](../../routes/), [`views/`](../../views/),
  [`resources/`](../../resources/), [`assets/`](../../assets/): resources shipped
  with Core (debugger routes/views, language files, code templates, shell
  completion, registries).
- [`bin/`](../../bin/): CLI entry (`windwalker.php`), release and style tooling.

Core is built on the other `windwalker/*` packages (`di`, `event`, `http`,
`attributes`, `data`, `cache`, `crypt`, `filesystem`, `filter`, ...). Core
assembles them into an application; it does not re-implement them. Database,
ORM, query, session, renderer, and similar engines come from those packages
and are wired in through providers and managers.

## Runtime and container layers

[`Runtime`](../../src/Core/Runtime/Runtime.php) is a static bootstrap holder. A
host entry point calls `Runtime::boot($rootDir, $workDir)`, then
`Runtime::loadConfig()` with the host's runtime config file. This creates the
root DI `Container` and its `Config` parameters. Runtime-level services come
from `RuntimeProvider`.

The container is layered; `AppLayer` names the levels:

| Level | Constant | Created by | Lifetime |
|-------|----------|------------|----------|
| 1 | `AppLayer::RUNTIME` | `Runtime::getContainer()` | process |
| 2 | `AppLayer::APP` | `$container->createChild()` for an application | application (long-lived in server mode) |
| 3 | `AppLayer::REQUEST` | `WebApplication::createContext()` | one request |

Providers declare where they apply with `providedIn()` / `canProvide()`
(see `WebProvider`). Request-scoped services (request, `AppContext`,
`ControllerDispatcher`, `Navigator`, `AppState`) must live at level 3 so that
state never leaks between requests in long-running servers (Swoole, CLI
server). Providers can implement `RequestBootableProviderInterface` /
`RequestReleasableProviderInterface` (see
[`src/Core/DI/`](../../src/Core/DI/)) to hook request start and cleanup.

### Request-scoped service isolation

`WebApplication::boot()` only boots levels 1-2 (RUNTIME → APP) once; it never
reruns. Each request gets its own level 3 container from
`createContext()`/`createChild()`, which is discarded when the request ends.
A level 3 container that looks up a service it has no local definition for
falls back to its parent's (level 2) definition, cloning it; by default that
clone shares the parent's already-resolved instance, so services registered
once at the app level are effectively singletons shared across requests.

Some services must not be shared this way — for example anything that holds
a live connection or per-request mutable state (`DatabaseManager`,
`SessionManager`, `PathResolver`, `CacheManager`, `MailerManager`, and other
`Manager`/`Factory` classes). These are marked with the `#[Isolation]`
attribute (or registered with `DIOptions(isolation: true)`). When a child
container clones a definition marked isolated, it resets the clone's cache
before use, so the next resolution rebuilds a fresh instance instead of
reusing the parent's cached one — giving each request its own instance while
the definition itself is still declared once, at the app level.

This rebuild only works for definitions registered as a rebuildable factory
(`$container->prepareSharedObject()` / `prepareObject()`, used internally by
`AppProvider`); a literal pre-built value shared via
`$container->share($id, $value)` has nothing to rebuild from and stays
shared regardless of the attribute. See
[`testing-architecture.md`](testing-architecture.md#composing-request-layer-providers-and-bindings-per-test)
for how tests can use this to compose request-layer state without rebooting
the application.

## Applications

Applications live in [`src/Core/Application/`](../../src/Core/Application/).

- `WebApplication`: HTTP application. `boot()` registers `AppProvider`, then
  every config section through `registerAllConfigs()`, then the middleware
  list, then `WebProvider`.
- `ConsoleApplication` ([`src/Core/Console/`](../../src/Core/Console/)):
  Symfony-based CLI. Registers `AppProvider` and `ConsoleProvider`, loads
  `commands` from config via a container command loader.
- `MicroApplication`: minimal routed app without the full config pipeline.
- `WsRootApplication` ([`src/WebSocket/`](../../src/WebSocket/)): WebSocket
  application.
- `AppType` (`WEB`, `CLI_WEB`, `CONSOLE`) and `AppClient` describe the
  runtime; `AppVerbosity` controls debug output.
- A host normally subclasses `WebApplication` / `ConsoleApplication` and
  overrides `booting()` / `terminating()`.

Shared behavior is in `ApplicationTrait` (config, paths via
`PathResolver` aliases such as `@root`, `@etc`, `@resources`; debug flags;
secret; maintenance), `ServiceAwareTrait`, and `ProcessRunnerTrait`.

## Configuration and providers

Config is a nested array loaded into the container's `Config` parameters. The
host decides file layout. Core's contract:

- Each top-level array section is processed by
  `Container::registerByConfig()`: `providers`, `bindings`, `aliases`,
  `listeners`, `extends`, etc. A section with `'enabled' => false` is skipped.
  The `di` section is processed last.
- Providers that implement `BootableDeferredProviderInterface` are booted
  after all sections are registered.
- Top-level `middlewares`, `listeners`, and `commands` are read by the
  applications. `factories.*` and `@alias` keys in the runtime config tell the
  entry points how to build the apps and where paths point.
- Core's providers are in [`src/Core/Provider/`](../../src/Core/Provider/)
  (App, Web, Request, Router, Session, Security, Logger/Monolog, Mailer, Edge,
  Event, ErrorHandling, Whoops, DateTime, Auth, Console, Runtime).

## Web request lifecycle

1. The host's `www/index.php` boots `Runtime`, resolves the HTTP server and
   `WebApplication`, calls `bootForServer()`, then
   `executeServerEvent()` per request.
2. `createContext()` creates the level 3 child container, shares the PSR
   request, registers listeners and request-bootable providers, and resolves
   `AppContext` (the per-request facade: request, URL vars, state, controller,
   messages, `redirect()`, `renderView()`).
3. `runContext()` emits `BeforeRequestEvent`, builds the middleware chain
   (config `middlewares`, ending with the controller dispatch), emits
   `BeforeAppDispatchEvent`, then runs the chain via `MiddlewareRunner`.
4. `RoutingMiddleware` matches the request with `Router` and fills the route
   into `AppContext`: handler, view, URL vars, route-level middleware.
   Routing events: `BeforeRoutingEvent`, `AfterRoutingEvent`,
   `RoutingNotMatchedEvent`. Route-level middleware
   (`Route::middleware()`) and attribute middleware
   ([`src/Core/Middleware/`](../../src/Core/Middleware/)) wrap the handler.
5. `ControllerDispatcher::dispatch()` resolves the handler: a callable, a
   `Class::method` string, or a class whose default task is derived from the
   HTTP method (`get` → `index`, `post`/`put`/`patch` → `save`, `delete` →
   `delete`; overridable with `#[TaskMapping]`). Arguments are injected by the
   container and request attributes. It emits `BeforeControllerDispatchEvent` /
   `AfterControllerDispatchEvent`.
6. A controller returns a PSR response, a redirect, data, or the result of
   `$app->renderView(...)`. If a GET route has no handler, `RoutingMiddleware`
   falls back to `DefaultController`, which renders the route's view.
7. `View` renders a view model: it emits `PrepareDataEvent` and
   `BeforeRenderEvent`, calls the `#[ViewPrepare]` method (or
   `ViewModelInterface::prepare()`), resolves layout and template paths, sets
   `HtmlFrame` metadata and assets, then renders through
   `RendererService` (Edge templates by default, with `ThemeInterface` for
   form/UI themes).
8. The response is returned and emitted by the server; `stopContext()` emits
   `AfterRespondEvent`, request-releasable providers clean up, and
   `terminate()` emits `TerminatingEvent` on shutdown.

Uncaught exceptions are handled in `WebApplication::dispatch()`: validation
and CSRF failures and non-GET errors redirect back with a flash message
unless the call is API/JSON or debug mode is on; otherwise they propagate to
the error handlers in [`src/Core/Error/`](../../src/Core/Error/) (Whoops in
debug).

## Console lifecycle

`bin/windwalker.php` boots `Runtime`, resolves `factories.console`, and runs
it. `ConsoleApplication::boot()` registers providers and commands from the
`commands` config. Commands are `CommandInterface` classes (Core's own are in
`src/Core/*/Command/` and [`src/Core/Command/`](../../src/Core/Command/)) and
are lazily built by the container. Console events are in
[`src/Core/Events/Console/`](../../src/Core/Events/Console/).

## Modules, controllers, and view models

Core supplies the contracts; the host supplies the concrete modules.

- Route → handler (controller class, method, or callable) and optional view
  (view model class). Route building is in
  [`src/Core/Router/`](../../src/Core/Router/) (`RouteCreator`,
  `RouteBuilder`, `Route`, `Navigator` for URL generation).
- Controllers: [`src/Core/Controller/`](../../src/Core/Controller/).
  `#[Controller]` and request attributes (`#[Input]`, `#[QueryParam]`,
  `#[BodyValue]`, `#[RouteParam]`, `#[UrlVar]`, `#[Header]`, `#[File]`) and
  `#[Method]`, `#[Json]`, `#[JsonApi]`, `#[Csrf]`, `#[Transaction]`,
  `#[Filter]`, `#[Assert]` decorate handlers. Handlers are registered in
  [`etc/attributes.php`](../../etc/attributes.php).
- View models: `#[ViewModel(layout:, css:, js:, modules:)]` on the class,
  `#[ViewPrepare]` on the prepare method, `#[ViewMetadata]` on metadata
  methods. See [`src/Core/View/`](../../src/Core/View/).
- Modules: `#[Module]` + `AbstractModule` group a feature's controller,
  views and config; `ConfigModule` provides module-scoped config.
- Attribute handlers implement `ContainerAttributeInterface` (see
  `Windwalker\DI\Attributes`) and are applied by the container when it
  resolves or calls the target, so changes to attributes must keep
  `etc/attributes.php` in sync.

### Typical feature flow (route → view)

1. A route file maps a URL to a view model (`->view(SomeView::class)`) or a
   controller; a GET route with neither falls back to `DefaultController`,
   which renders the view named by the route.
2. The view model's `#[ViewPrepare]`/`prepare(AppContext, View)` method returns
   the data array for the template; constructor dependencies (ORM, pagination
   factory, ...) come from the request container.
3. `#[ViewMetadata]` methods receive the injected `HtmlFrame` and view data to
   set title, meta tags, and assets.
4. The Edge (Blade-compatible, framework-independent) template renders the
   layout named in `#[ViewModel(layout:)]`.

The `generate` console command (`GenerateCommand` and `SubCommand/*`: view,
route, controller, model, entity, middleware, ...) scaffolds these classes, and
its templates live in [`resources/templates/`](../../resources/templates/).

### Debug mode and profiler

Debug mode comes from the `app.debug` config (usually driven by an
environment variable); the debugger package adds the profiler and error pages
and is enabled per environment through `#[ConfigModule(env: ...)]` on its
config file. `ConfigModule` also supports `enabled`, `priority`, and
`belongsTo` (class or Composer package that must be present). Tests normally
turn the profiler off (`disableDebugProfiler()`).

## Packages (extensions)

An extension implements `AbstractPackage` (`install()`, optional
`migrate()`), and declares itself in its `composer.json` under
`extra.windwalker.packages`. `PackageRegistry::discover()` scans the host's
`composer.json` and `vendor/composer/installed.json` for those declarations
and instantiates the packages. `PackageInstaller` / `PackageMigrator` and the
`Package/Command` commands copy config, routes, assets, migrations and seeders
into the host. Core's own `CorePackage` and `DebuggerPackage` follow the same
contract.

## Capability areas

Directories in [`src/Core/`](../../src/Core/), grouped by concern:

- Application & runtime: `Application`, `Runtime`, `Provider`, `DI`,
  `Package`, `Module`, `State`, `Manager`, `Factory`, `Service`
- HTTP & routing: `Http`, `Router`, `Middleware`, `Controller`, `Response`,
  `Link`, `Security`, `Session`, `Auth`, `Error`
- Views & UI: `View`, `Renderer`, `Edge`, `Html`, `Asset`, `Theme`, `Form`,
  `Language`, `Pagination`
- Data: `Database`, `Migration`, `Seed`, `Queue`, `Schedule`, `Mailer`,
  `Crypt`, `DateTime`
- Tooling: `Console`, `Command`, `Generator`, `CliServer`, `Composer`,
  `Profiler`, `Filesystem`, `Utilities`
- Events: `Event` (emitter, collectors), `Events` (typed web/console event
  objects)

Managers (`Core/Manager/*Manager`, `Factory/*Factory`) hold named instances
(database, cache, logger, mailer, session, crypto, hasher) configured from the
host's config sections.

## Events

Events are typed objects dispatched through `CoreEventEmitter` /
`CoreEventAwareTrait`. The application and `AppContext` both dispatch; listeners
come from config `listeners` (global and per section) and attributes
(`EventSubscriber`). When adding a lifecycle hook, add a typed event under
[`src/Core/Events/`](../../src/Core/Events/) rather than a callback option.

## Tests layout

[`tests/Core/`](../../tests/Core/) mirrors `src/Core/`. Reusable test support
(stubs, traits, the app-backed `AbstractTestApp`) ships under
[`src/Core/Testing/`](../../src/Core/Testing/) — Core's production autoload —
so any host app can reuse it from its own tests; `tests/` only keeps
`tests/TestApp.php` as the reference example extending `AbstractTestApp`, and
the app-aware bootstrap in [`tests/bootstrap.app.php`](../../tests/bootstrap.app.php).
See [`testing-architecture.md`](testing-architecture.md) for per-layer test
strategy and
[`../instructions/test.instructions.md`](../instructions/test.instructions.md).
