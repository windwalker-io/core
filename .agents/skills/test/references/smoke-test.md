# Smoke tests and temporary executable files

## Applicability

- Purpose: smoke tests beyond unit tests, or temporary executable files for verifying application/package behavior.
- Use when you need the host application's runtime or services.
- For PHPUnit writing and coverage rules, see the testing guide.

## Temporary files

- Location: repository `tmp/` (not the root or `www/`).
- Ignore rule: `tmp/.gitignore` (preserve the directory's ignore file).
- Filename: use a unique name, such as `tmp/smoke-schedule.php`.
- Cleanup: delete only files created for this check; do not empty the entire `tmp/` directory.

## Discovering and generating commands

- Run from the repository root.
- List commands: `php windwalker list`, `php windwalker help exec`, `php windwalker g`
- Generator help: `--about` (do not use `-h`; that shows the outer generator's help)
- Commands are output to `src/Command/` by default; `--ns` changes only the namespace, while `--dir` changes the output directory.
- Auto-register: `--auto-register <name>` writes to `resources/registry/commands.php`.
- Non-interactive: add `--no-interaction`.
- Avoid using `--force` to overwrite an existing command.
- Generated commands implement `CommandInterface`; `execute()` receives `IOInterface`; return `0` on success.
- Cleanup: delete the generated class and its registration entry; do not delete `resources/registry/commands.php`.

## Custom scripts

- Define them in `resources/registry/scripts.php` (loaded by `etc/app/console.config.php`).
- List them with `php windwalker run`.
- Run them with `php windwalker run <scriptName>`.
- Read the definition before running it; `preparedev` resets migrations and imports seed data.
- For one-off checks, prefer existing entry points. If necessary, register a temporary entry with a unique name, then remove only that entry afterward.

## REPL and PHP execution (`exec`)

- Commands: `php windwalker exec` (PsySH), `--code '<expr>'`, `<path>` (file mode).
- Available variables: `$app` (application), `$io` (console input/output).
- Services and configuration: `$app->service(...)`, `$app->config(...)`, `$app->path(...)`.
- File mode: pass the path as a positional argument, not `--file`; PHP files must start with `<?php` and `declare(strict_types=1);`; do not restart the application inside the file.
- Known issue: file mode in `src/Core/Command/ReplCommand.php` includes an undefined `$real`; do not use it as a verification method until fixed.
- `--code` evaluates an expression first, then retries it as a statement if that fails, which may execute it twice; for checks that write data or need controlled error handling, use a command instead.

## Runtime boundaries

- Use for real host application services and environment, not PHPUnit fixtures.
- The database, queue, and external services must be available; confirm the target environment before writing data.
- Do not reset or import database seeds without authorization.
- The console is not a web request; `src/Console/Application.php` does not enable the web simulator by default.
- For HTTP, routing, session, or other state, use the correct web entry point.
- Define success and failure conditions before checking; use bounded, non-interactive commands with explicit exit codes.
- Output or dumps alone are not sufficient evidence of success.
- For persistent workers or servers, limit the run time, stop only the process started for this check, and clean up temporary files, registry entries, and artifacts.
- Clearly report missing prerequisites or incomplete checks; do not present them as passing.
- Smoke tests supplement PHPUnit and do not replace it; package regression tests belong in that package's test suite.

## Web and rendering smoke tests

- Applicable when real HTTP requests, request-scoped services, or rendered HTML are needed.
- Temporary components: controller and route; add middleware only when the behavior belongs to the request pipeline.
- They must be placed in normal application locations to participate in autoloading and routing (they may appear in Git status).
- This refers to static HTML rendering tests; for E2E testing, see `e2e-testing.md`.

Generator help:

- `php windwalker g controller --about`, `g middleware --about`, `g view --about`
- Controllers default to `src/Module/` and `App\Module`; a stage-qualified name (such as `'Front\Smoke\SmokeController'`) avoids the stage prompt.
- Middleware defaults to `src/Middleware/` and `App\Middleware`.
- Generating a file does not register it in the request pipeline.

Route registration:

- Follow adjacent route definitions; `routes/main.route.php` loads the frontend and backend groups.
- Frontend: `routes/front/*.php`, `routes/custom/front/*.route.php`
- Backend: `routes/admin/*.php`, `routes/custom/admin/*.route.php` (URL prefix `/admin`)
- Example: add `$router->get('smoke_check', '/__smoke-check')->handler(SmokeController::class);` under `routes/front/`.
- View model: `->view(...)`; refer to `src/Module/Front/Home/HomeView.php` (`#[ViewModel]`, `#[ViewPrepare]`, module-level layout).
- Probe middleware: `->middleware(SmokeProbeMiddleware::class)`
- Before routing: inspect `middlewares` in `etc/app/main.config.php` and the merged configuration; preserve `RoutingMiddleware`.

Verifying a real request:

- Start the server on an explicit port (see the CLI guide), then make a request with an HTTP client or browser.
- Normal entry point: the web entry point; development mode: `/dev.php` (which includes `www/index.php`).
- Check the HTTP status, headers, and content.
- Inspect before following redirects: URL normalization, maintenance mode, locale, and backend authentication may all intercept the request early.
- Frontend middleware loads configuration and assets, and may require a database and frontend assets.
- Do not bypass authentication or CSRF protection just to get a passing result.
- If JavaScript or CSS is involved, inspect console and network errors in the browser.

Cleanup and safety:

- Keep endpoints local-only and short-lived.
- Do not expose secrets, arbitrary code execution, or destructive operations through endpoints.
- Remove the controller, middleware, route, view/layout, and generated assets or registration entries.
- Preserve unrelated files; stop only the server process started for this check.

## Related documentation

- [Basic console commands](https://windwalker.io/documentation/framework/console/basic-commands.md)
- [Console server command (`server`)](https://windwalker.io/documentation/framework/console/commands/server.md)
- [Running code (`exec` / `run`)](https://windwalker.io/documentation/framework/console/commands/running-code.md)
- [Code Generator](https://windwalker.io/documentation/framework/console/commands/code-generator.md)
- [Request lifecycle](https://windwalker.io/documentation/framework/core-concepts/applications/request-lifecycle.md)
- [Middleware](https://windwalker.io/documentation/framework/core-concepts/http-routing/middleware.md)
- [Routing](https://windwalker.io/documentation/framework/core-concepts/http-routing/routing.md)
- [Testing services](https://windwalker.io/documentation/framework/services/testing.md)
