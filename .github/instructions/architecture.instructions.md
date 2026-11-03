# architecture.instructions.md

Mandatory rules when changing Core's structure. Background and the request
flow are in [`../docs/architecture.md`](../docs/architecture.md); read it before
changing applications, providers, routing, controllers, views, or packages.

## Placement

- Put code in the `src/Core/<Capability>/` directory that owns the concern;
  console primitives in `src/Console/`, WebSocket in `src/WebSocket/`, debugger
  in `src/Debugger/`. Do not add a top-level area without a clear reason.
- Core provides contracts and wiring. Concrete modules, routes, config values,
  migrations, and assets belong to the host app, not Core.
- Reuse `windwalker/*` packages (DI, event, http, attributes, ...) instead of
  re-implementing their features.

## Container layers

- Request-scoped state (request, `AppContext`, `AppState`, anything derived
  from the request) must be registered at `AppLayer::REQUEST`; never store it
  in a runtime or app level service, since servers reuse those across requests.
- Use `providedIn()` / `canProvide()` to restrict a provider's layer, and the
  request bootable/releasable provider interfaces for per-request setup and
  cleanup.
- Resolve services through the container; do not read globals or
  `Runtime::getContainer()` inside request-scoped code when `AppContext` or
  injection is available.

## Paths and debug mode

- `WINDWALKER_*` constants (`WINDWALKER_ROOT`, `WINDWALKER_CACHE`,
  `WINDWALKER_TEMP`, `WINDWALKER_RESOURCES`, `WINDWALKER_DEBUG`, ...) are
  defined by and scoped to the host app's bootstrap; they are not part of
  Core's internal API.
- Inside `src/`, resolve filesystem paths through `PathResolver`
  (`$app->path('@root/...')` or inject `PathResolver`) instead of referencing
  `WINDWALKER_*` path constants directly.
- Inside `src/`, read debug mode via `ApplicationInterface::isDebug()`
  (`$app->isDebug()`) instead of reading `WINDWALKER_DEBUG` directly.
- Existing `WINDWALKER_*` usages in `src/` predate this rule; do not copy them
  as a pattern for new code, and prefer `PathResolver`/`isDebug()` when
  touching that code.
- When fixing an existing `WINDWALKER_*` usage: if `PathResolver` /
  `ApplicationInterface` can be injected or otherwise obtained correctly at
  that location, replace it directly. If the code runs in an isolated context
  where injection isn't possible (e.g. before the container/app exists, a
  static/global context, or a class outside the DI graph), do not guess a
  workaround — ask the user how to proceed.

## Extension points

- Add lifecycle hooks as typed events in `src/Core/Events/` (or a feature's
  `Event/` directory), not ad-hoc callbacks.
- A new attribute must be registered in [`etc/attributes.php`](../../etc/attributes.php)
  with the correct `AttributeType` targets.
- A new provider is registered through config sections (`providers`, `di`),
  not by hard-coding it in an application class, unless it is a core layer
  (`AppProvider`, `WebProvider`, `RequestProvider`, `ConsoleProvider`).
- Extensions integrate through `AbstractPackage` and the
  `extra.windwalker.packages` composer key; keep install/migrate steps
  idempotent.

## Compatibility

- Applications, middleware order, event names, config keys, and attribute
  names are public API; change them deliberately and keep them backward
  compatible.
- Update [`../docs/architecture.md`](../docs/architecture.md) when a change alters
  the layout, container layers, request/console lifecycle, or package
  discovery.
