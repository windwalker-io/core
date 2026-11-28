---
applyTo: "**/{test,Test,tests,Tests}/**/*.php"
---

# Coverage testing and guidelines

For the workflow to find and fill coverage gaps, see the [test skill](../SKILL.md).

## Generate a coverage report with PhpUnit

- By default, use PHPUnit + Xdebug to generate a coverage report under `tmp/coverage`. Ensure Xdebug is installed and enabled in `php.ini`.
- If Xdebug is unavailable, use `--coverage-php` to generate a coverage data file, then use `phpdbg` or another tool to generate the report.

## Principles for deciding what to cover

### Getters/setters without logic or simple proxy methods do not need separate tests and should be excluded from code coverage

Do not write separate tests just to execute pure getters/setters (which only return or assign a property, optionally with `return $this`). If meaningful coverage for such a method cannot be
obtained through other behavior tests, exclude it by adding `@codeCoverageIgnore` to the method's docblock. If logic is later added (validation, normalization, lazy initialization, side effects, etc.),
**you must** remove the ignore annotation and test the behavior.

```php
/**
 * @codeCoverageIgnore
 */
public function setOptions(array $options): static
{
    $this->options = $options;

    return $this;
}
```

Simple proxy methods that only call another method or service without adding behavior may also be excluded. If parameters or return values are transformed, validated, or otherwise processed, their behavior must be tested.

```php
/**
 * @codeCoverageIgnore
 */
public function createAnyInstance(array $options): static
{
    return $this->builder->createAnyInstance($options);
}
```

### Do not ignore branches with logic or that throw errors

`@codeCoverageIgnore` applies only to getters/setters without logic and simple proxy methods. Any branch with logic (conditions, transformations, fallbacks, side effects) or that throws an exception,
even if it is only a few lines, **must** be tested. Do not ignore such branches to improve coverage. Only genuinely unexecutable code (for example, an unavailable external driver,
or process-level `exit` / signal handling) may be ignored, and the reason must be stated in the annotation.

A purely defensive or fallback branch may also be ignored if it has been shown to be unreachable through public behavior (for example, because the caller already guarantees the condition).
Wrap it in `@codeCoverageIgnoreStart` / `End` (or mark the whole method) and explain why it is unreachable in a comment. Verify this claim first; if it is actually reachable, it must be tested.
