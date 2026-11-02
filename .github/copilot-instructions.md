# Copilot instructions

## Purpose and project structure

- This repository is the `windwalker/core` PHP framework package. Its `.github/`
  directory also contains reusable agent guidance, documentation, prompts, and
  skills.
- Keep reusable guidance and templates generic; do not add product
  implementation details, secrets, or environment-sensitive configuration to
  those resources.
- The main guidance and resource locations are:
  - `../AGENTS.md`: general contributor and agent guidance
  - `docs/index.md`: documentation overview and topic index
  - `docs/templates/`: documentation templates and conventions
  - `instructions/`: reusable repository instructions
  - `prompts/`: AI workflow prompt templates
  - `skills/index.md` and `skills/templates/`: reusable skills and templates
  - `workflows/`: GitHub Actions workflows
- Keep file roles distinct: this file and `../AGENTS.md` define repository
  rules; skill files define reusable skills; prompt files provide reusable
  prompts; `docs/` contains human-readable references and templates.

## Loading documentation

- Do not read all of `docs/` up front unless the task requires it.
- Start with `docs/index.md` to identify relevant material, then read only the
  topic-specific documents needed.
- When adding a documentation category, update `docs/index.md` and this
  guidance so contributors know when to load it.
- Keep the documentation index and guidance concise and expandable.
- Before writing Core integration tests, read
  `docs/host-application-and-testing.md` to identify the host-app bootstrap,
  configuration, and external services the test needs.
- A consuming app may have any name or location. If no host app is clearly
  available in the workspace, treat Core as standalone and use its own test
  setup; do not assume or depend on Earth.

## Package scope

- This repository is `windwalker/core`, a reusable PHP framework package.
- The `Windwalker\\` namespace maps to `src/`; development tests use
  `Windwalker\\Tests\\` from `tests/`.
- Keep framework changes in this package. Do not apply Windwalker Starter
  application conventions such as `App\\`, front/admin modules, Vite assets,
  or application migrations here.
- Check the package's own code and tests for established APIs and patterns
  before introducing new abstractions or dependencies.

## PHP conventions

- The package requires PHP `>=8.4.6`; preserve that compatibility floor.
- Use `declare(strict_types=1);` and follow `.php-cs-fixer.dist.php`.
- Keep public API changes deliberate and compatible with existing consumers.
- Add or update focused tests for behavior changes. Do not change unrelated
  framework behavior or tests.
- Keep unit tests independent of the consuming app when possible. For tests
  requiring a booted app or real services, declare and configure those
  integration prerequisites explicitly.

## Validation

- Run `composer test` for the PHPUnit suite. Some database or service-backed
  tests require their corresponding local DSN or service configuration.
- Run `composer cs-check` for the configured PHP-CS-Fixer check.
- `composer cs-fix` modifies files; use it only when formatting changes are
  intended.
- For changes limited to Markdown guidance or templates, confirm the content is
  Markdown and follows the intended file role; PHP checks are not needed.

## Change boundaries

- Update package documentation when changing user-facing behavior or APIs.
- Keep documentation and templates concise, neutral, and reusable. Avoid
  customer-, product-, or team-specific details unless explicitly required.
- Preserve clear folder responsibilities and keep shared guidance consistent.
- Do not edit files under `vendor/` belonging to other packages as part of a
  core change.
- Avoid running database setup or destructive commands without explicit
  authorization and a confirmed safe target.
