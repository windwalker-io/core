# Copilot instructions

`windwalker/core` is a reusable PHP framework package. The `Windwalker\` namespace
maps to `src/`; tests use `Windwalker\Tests\` from `tests/`.

## Where to find things

Load only what the task needs; do not read all of `docs/` up front.

- [`../AGENTS.md`](../AGENTS.md): general contributor and agent rules
- [`docs/index.md`](docs/index.md): documentation index, start here for any topic
- [`docs/architecture.md`](docs/architecture.md): Core package layout, container layers, web/console lifecycle, packages (read before structural changes)
- [`docs/testing-architecture.md`](docs/testing-architecture.md): which test tier to use per architecture layer, with the standard `TestWebAppFactory` recipe (read before writing tests)
- [`docs/host-application-and-testing.md`](docs/host-application-and-testing.md): host-app bootstrap and test prerequisites (read before writing integration tests)
- [`docs/templates/`](docs/templates/): documentation templates and conventions
- [`instructions/architecture.instructions.md`](instructions/architecture.instructions.md): placement, container-layer, and extension-point rules
- [`instructions/test.instructions.md`](instructions/test.instructions.md): Core test paths and helpers
- [`instructions/test-common.instructions.md`](instructions/test-common.instructions.md): shared PHPUnit style and quality rules
- [`instructions/`](instructions/): other reusable instructions
- [`prompts/`](prompts/): AI workflow prompt templates
- [`skills/index.md`](skills/index.md): reusable skills; templates in [`skills/templates/`](skills/templates/)
- [`workflows/`](workflows/): GitHub Actions workflows

File roles: this file and `../AGENTS.md` define rules; skills, prompts, and
`docs/` hold reusable skills, prompts, and human-readable references.
Keep reusable content generic: no product details, secrets, or
environment-specific configuration.

## Scope

- Keep changes in this package. Do not apply Windwalker Starter conventions
  (`App\`, front/admin modules, Vite assets, app migrations) here.
- Check existing code and tests for established APIs before adding abstractions
  or dependencies.
- Ask the user before editing any other package under `vendor/` (cross-package
  edits). Editing the host application is allowed when the task requires it.

## PHP conventions

- PHP `>=8.4.6`; keep that compatibility floor.
- Use `declare(strict_types=1);` and follow `.php-cs-fixer.dist.php`.
- Keep public API changes deliberate and backward compatible.
- Add or update focused tests for behavior changes; leave unrelated behavior
  and tests alone.
- Prefer unit tests independent of a host app; for tests needing a booted app
  or real services, declare those prerequisites explicitly.

## Testing with a host app

- The host app may have any name or location. If none is clearly available,
  treat Core as standalone and use its own test setup; do not assume Earth.
- If a host app exists, pass its `phpunit.app.xml` to PHPUnit with `-c`.
  Otherwise follow the fallback order in
  [`docs/host-application-and-testing.md`](docs/host-application-and-testing.md).

## Validation

- `composer test`: PHPUnit suite (database/service tests need local DSN/config).
- `composer cs-check`: PHP-CS-Fixer check.
- `composer cs-fix`: modifies files; use only when formatting changes are intended.
- Style commands run from Core's root via `bin/php-cs-fixer`, which prefers
  Core's `vendor/bin/php-cs-fixer`, then a parent host app's fixer, and fails
  with an install message if none exists.
- Markdown-only changes: confirm the content is Markdown and fits the file's
  role; PHP checks are not needed.

## Documentation

- Update docs when changing user-facing behavior or APIs.
- Keep docs and templates concise, neutral, and reusable.
- When adding a documentation category, update `docs/index.md` and the
  "Where to find things" list above.
- When listing files or directories as an index, start each item with a
  Markdown link followed by a description: `[label](relative/path): description`.
  Elsewhere, use inline code for paths and identifiers; link only when useful.

## Safety

- Do not run database setup or destructive commands without explicit
  authorization and a confirmed safe target.
