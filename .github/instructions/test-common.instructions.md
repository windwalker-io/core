---
applyTo: "**/{test,Test,tests,Tests}/**/*.php"
---

# Shared PHP testing instructions

These rules are reusable across PHP projects using PHPUnit. They do not
require a particular framework, application, repository layout, or test helper.
Check the project's selected PHPUnit version before using version-specific
APIs.

## Test scope and quality

- Prefer existing project helpers over duplicating dependency-injection,
  database, or controller setup. Keep tests independent of real services
  unless those services are part of the behavior under test.
- Check the selected PHPUnit configuration for disabled suites or service
  scopes. Tests that did not run are not passing tests. Use CLI options or a
  temporary configuration based on the appropriate distribution config to
  enable the requested scope, preserve existing local configuration, and
  remove temporary configuration when finished.
- When adding missing tests, use coverage to identify uncovered lines and
  branches in the requested scope when a coverage driver is available.
  Assert meaningful behavior, including edge cases and error paths, and
  rerun the same coverage scope. Do not inflate coverage with assertion-free
  tests or new exclusions. Report unavailable coverage rather than claiming
  unverified gaps are covered.
- Add class docblocks to test-only fixtures and integration targets explaining
  their role, which behavior depends on them, and when they can be removed.
- Do not run real-database suites in parallel when they share mutable fixtures
  or database state.

## Lifecycle hooks

- Prefer PHPUnit's `#[Before]`, `#[After]`, `#[BeforeClass]`, `#[AfterClass]`,
  `#[PreCondition]`, and `#[PostCondition]` attributes over conventional hooks
  such as `setUp()` and `tearDown()`.
- Import attributes from `PHPUnit\Framework\Attributes`, use descriptive
  method names and `void` return types, and make class-level hooks static.
- Avoid empty hooks. Preserve inherited parent and trait behavior and
  execution order when migrating hooks.

## Filesystem fixtures

- Normalize paths before asserting equality when Windows and Unix separator
  differences are irrelevant. Do not compare raw platform-dependent paths.
  Distinguish path normalization from resolving an existing path with
  `realpath()`.
- Use isolated virtual filesystem fixtures when the API supports stream
  wrappers and native filesystem behavior is not under test. Otherwise use
  real temporary files and clean them up after the test.

## Coverage exclusions

- Do not write separate tests solely for logic-free getters/setters that only
  return or assign a property, optionally returning `$this`, or simple proxy
  methods that merely delegate without changing behavior. If otherwise
  uncovered, mark these methods with `@codeCoverageIgnore`.
- Remove that exclusion and test the behavior if validation, normalization,
  lazy initialization, side effects, or parameter/return transformations
  are added.
- Test reachable branches with conditions, transformations, fallbacks, side
  effects, or thrown exceptions. Do not exclude them just to raise coverage.
- Exclude genuinely unexercisable code only with an explicit reason, such as
  an unavailable external driver or process-level exit/signal handling.
  Verify that defensive branches are unreachable through public behavior
  before using `@codeCoverageIgnoreStart` / `@codeCoverageIgnoreEnd` or a
  method-level exclusion, and explain why they cannot be reached.

## PHPUnit APIs and test doubles

Use the following APIs when running PHPUnit 13; check the selected test
runner's version before relying on version-specific methods.

- Replace deprecated `expectExceptionMessage()` with
  `expectExceptionMessageIsOrContains()` for an exact-or-substring match,
  `expectExceptionMessageIs()` for an exact match, or
  `expectExceptionMessageMatches()` for a regular-expression match.
- Call mock invocation matchers on `$this`, not `self::`, including `once()`,
  `never()`, `any()`, `exactly()`, `atLeastOnce()`, and `atMost()`.
- Isolate collaborators with test doubles instead of wiring real services
  or databases for every test. Use real dependencies when the integration
  itself is the behavior under test.
- Use `$this->createStub()` when only return values are needed, configure
  it with `method()->willReturn()`, and do not call `expects()` on a stub.
- Use `$this->createMock()` when verifying interactions with `expects()`.
  A mock with no expectations produces a notice; use a stub instead.
- For multi-interface doubles, use `createStubForIntersectionOfInterfaces()`
  or `createMockForIntersectionOfInterfaces()`.
- Use `willReturnCallback()`, `willReturnOnConsecutiveCalls()`, or
  `willReturnMap()`; do not use the removed `withConsecutive()`.
- Do not use `setMethods()`, `addMethods()`, or `onlyMethods()`; mock an
  interface or extract a collaborator instead.
- Prefer anonymous classes or small named stubs for plain value objects.

```php
$repository = $this->createStub(UserRepository::class);
$repository->method('find')->willReturn($user);

$logger = $this->createMock(LoggerInterface::class);
$logger->expects($this->once())->method('error');
```
