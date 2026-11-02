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
