# Documentation index

This directory stores documentation for the Windwalker Core package. Keep
references concise and focused on package architecture, host-app integration,
testing requirements, and Markdown templates.

## Scope

- Core package architecture and integration guidance (see `architecture.md`)
- Requirements for standalone and host-app-backed testing
- Reusable package documentation templates

## Organization

- [`templates/`](templates/): Markdown document templates
- [`architecture.md`](architecture.md): package layout, container layers,
  application and request/console lifecycle, modules, packages
- [`testing-architecture.md`](testing-architecture.md): test tiers per architecture layer and the light-integration recipe
- [`host-application-and-testing.md`](host-application-and-testing.md): how a
  consuming app boots Core and what app context and services Core integration
  tests require
- [`integration-test-backlog.md`](integration-test-backlog.md): running list
  of code paths that need a future integration test instead of a unit test
- [`schedule-worker.md`](schedule-worker.md): resident CRON scheduling, CLI options,
  lifecycle and deployment behavior

## Maintenance

Update this index when adding a new documentation category. Keep its entries
accurate, concise, and specific to the Core package.
