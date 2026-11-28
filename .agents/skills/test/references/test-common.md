---
applyTo: "**/{test,Test,tests,Tests}/**/*.php"
---

# Shared testing instructions

For the complete workflow for adding, updating, and running tests (including local PHPUnit scope settings and using coverage), see the [test skill](../SKILL.md).
For rules on `@codeCoverageIgnore`, see the [coverage instructions](coverages.md).

## Prefer `#[Test]` for test methods

Prefer the `#[Test]` attribute (`PHPUnit\Framework\Attributes\Test`) over the `test` method-name prefix, and use descriptive names for the behavior under test.
Do not use the `@test` docblock annotation.

```php
// Bad
public function testFindReturnsNullWhenMissing(): void

// Good
#[Test]
public function findReturnsNullWhenMissing(): void
```

If you are only changing a few methods in a file that consistently uses the `test` prefix, keep the existing style.

## Prefer attributes for lifecycle hooks

Prefer `#[Before]` / `#[After]`, `#[BeforeClass]` / `#[AfterClass]`, and `#[PreCondition]` / `#[PostCondition]`
over traditional hooks (`setUp()`, `tearDown()`, etc.). Import them from `PHPUnit\Framework\Attributes`; use descriptive method names with `void` return types,
and make class-level hooks static. Avoid empty hooks. When migrating, preserve parent class/trait behavior and execution order.

## Document test fixtures and integration targets

Add a class docblock to fixtures and integration targets used only for testing. Describe their role and the behavior or tests that depend on them. Also state when they can be removed
(for example, when the behavior they cover has been removed and no tests reference them), so future maintainers can distinguish intentional test support code from dead code.

## Deprecated / recommended APIs (quick reference)

Add new “use / do not use” rules here as bullet points for easy scanning.

### Exception message assertions

Do not use `expectExceptionMessage()` (deprecated in PHPUnit 13).

- For exact or substring matches, use `expectExceptionMessageIsOrContains()`.
- For an exact match, use `expectExceptionMessageIs()`.
- For a regular expression, use `expectExceptionMessageMatches()`.

### PHPUnit mock: invocation count matchers

For invocation count matchers on PHPUnit mocks, use `$this->`, not `self::`
(applies to `once()`, `never()`, `any()`, `exactly($n)`, `atLeastOnce()`, `atMost($n)`).

```php
// Bad
$mock->expects(self::once())->method('foo');

// Good
$mock->expects($this->once())->method('foo');
```

### Test doubles (mocks / stubs)

When the subject under test calls many other objects or services, isolate it with test doubles instead of creating real data or connecting to real dependencies for every test.
Use real fixtures / databases only when the behavior under test is itself an integration.

Use the PHPUnit 13 API:

- For a double that only provides return values (no expectations), use `$this->createStub(Foo::class)` and configure it with `->method('x')->willReturn(...)`.
  Do not call `expects()` on a stub.
- Use `$this->createMock(Foo::class)` only when the test verifies interactions (such as `expects($this->once())`). A mock without `expects()` will produce a notice; use `createStub()` instead.
- For a double implementing multiple interfaces, use `createStubForIntersectionOfInterfaces()` / `createMockForIntersectionOfInterfaces()`.
- Use `willReturnCallback()`, `willReturnOnConsecutiveCalls()`, or `willReturnMap()`; `withConsecutive()` no longer exists.
- Do not use `setMethods()`, `addMethods()`, or `onlyMethods()`; mock an interface or extract a collaborator instead.
- For simple value objects, prefer an anonymous class or a small named test stub.

```php
// Good: only a return value is needed
$repo = $this->createStub(UserRepository::class);
$repo->method('find')->willReturn($user);

// Good: verify an interaction
$logger = $this->createMock(LoggerInterface::class);
$logger->expects($this->once())->method('error');
```
