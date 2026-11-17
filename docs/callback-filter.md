> Draft: to be merged into the official docs.

## Callback Filters and Validators

`FilterService` exposes `filterWith()` and `validateWith()`. They accept PHP `Closure` values directly, so you can
use first-class callable syntax such as `strval(...)` or `trim(...)`. Use them when a rule is a plain closure and you
do not want to write a string filter name such as `'string'` or `'trim'`. The string-based `filter()` and `validate()`
methods remain unchanged.

The string-based `filter()` and `validate()` methods accept only `string|array` commands, so a `Closure` passed to
them raises a `TypeError`. Use `filterWith()` and `validateWith()` for closure rules.

### Usage

Inject `FilterService` or call the global helpers `\Windwalker\filterWith()` and `\Windwalker\validateWith()`, which
resolve the service from the container.

```php
use Windwalker\Core\Service\FilterService;

// A single closure applies to the whole value.
$name = $filter->filterWith($input, trim(...));

// A list of closures forms a chain, applied in order.
$name = $filter->filterWith($input, [strval(...), trim(...)]);

// An associative array maps field keys to rules and applies them recursively.
$data = $filter->filterWith($input, [
    'foo' => [strval(...), trim(...)],
    'bar' => [
        'baz' => intval(...),
    ],
]);

// Validation: a closure returning a falsy value fails the validation.
$filter->validateWith($age, [
    static fn (mixed $v): bool => is_int($v) && $v >= 0,
]);
```

Equivalent calls with the global helpers:

```php
use function Windwalker\filterWith;
use function Windwalker\validateWith;

$name = filterWith($input, [strval(...), trim(...)]);
validateWith($age, static fn (mixed $v): bool => is_int($v));
```

### Rule Types

- **Closure**: applied to the whole value.
- **List of closures** (non-empty, all items are `Closure`): applied in order, with each result passed to the next
  closure.
- **Associative array**: maps field keys to sub-rules. Each sub-rule can be any of these three forms, and nesting is
  allowed.

### Behaviors

- **Missing keys are skipped.** If a rule key is absent from the input array, nothing is changed for that key.
- **Non-array values under a map rule are returned unchanged.** Filtering a scalar with an associative-array rule
  returns the scalar as-is.
- **Validation uses the original value for every closure.** In a chain, each validator receives the same value. Only
  the filter chain passes transformed values forward.
- **Validation failures throw `ValidateException`.** The message starts with `Value` for a whole-value rule, or with
  `Field "path"` for a nested field. The path uses dots, for example `Field "a.b" not match`.
- **Closures are detected by type, not by callability.** A string such as `'trim'` is not treated as a closure rule.
  Pass `trim(...)` or a closure instead.

### Limitations

- Piping syntax (`|>`) requires PHP 8.5 or later. The package supports PHP 8.4.6, so avoid it in shared code.
- These methods do not read string filter syntax. Use `createChain()` or `filter()` with a string when the rule comes
  from configuration.
