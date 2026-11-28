# PHP / Windwalker testing guide

## Test commands

- Projects usually have `phpunit` installed; run tests with `./vendor/bin/phpunit`.
- If the project has `phpunit.dist.xml` or `phpunit.xml`, use it directly as the configuration.
- If the project requires a database, get the connection details from `phpunit.xml`.

## Test environment

- Put test files under `tests/`, with a structure that closely mirrors `src/` to make corresponding files easy to find.
- The `tests/` directory usually contains `TestApp` and `bootstrap.xx.php` for initializing and sharing test environment settings.

## Other

- If you need a smoke test or a temporary test file, place it under the project's `tmp/` directory where possible. See `smoke-test.md`.
- When tests need temporary files or directories:
  - Use the project `tmp/` (`WINDWALKER_TEMP`, defaulting to `WINDWALKER_ROOT . '/tmp'`); do not use `sys_get_temp_dir()` / `tempnam()` or put them in the project root.
  - Use a path such as `WINDWALKER_TEMP . '/ww-test-' . uniqid()`.
  - Create it in `#[Before]` and remove it in `#[After]` (or a shutdown function).
- For app testing (tiers, automatic reset, request scope, helper traits), see `app-testing.md`.

## Documentation

- Windwalker provides testing helpers; review the documentation first: https://windwalker.io/documentation/framework/services/testing.md
- Search Windwalker documentation with the `windwalker-docs-search` MCP, or consult the public documentation index: https://windwalker.io/llms.txt
- For PhpUnit documentation, see `phpunit-testing.md`.
- For other related documentation, refer to the project's instructions or `docs/` directory.

## Related documentation

- [Test component](https://windwalker.io/documentation/components/test/index.md)
