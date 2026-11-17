<?php

declare(strict_types=1);

namespace Windwalker\Core\Service;

use Windwalker\Core\Runtime\Runtime;
use Windwalker\Filter\Exception\ValidateException;
use Windwalker\Filter\FilterFactory;
use Windwalker\Filter\FilterInterface;
use Windwalker\Filter\NestedFilter;
use Windwalker\Filter\ValidatorInterface;
use Windwalker\Utilities\Assert\Assert;

/**
 * The FilterService class.
 *
 *  Default filter syntaxes:
 *  - abs
 *  - alnum
 *  - cmd
 *  - email
 *  - url
 *  - words
 *  - ip
 *  - ipv4
 *  - ipv6
 *  - neg
 *  - raw
 *  - range(min=int, max=int)
 *  - clamp(min=int, max=int)
 *  - length(max=int, [utf8])
 *  - regex(regex=string, type='match'|'replace')
 *  - required
 *  - default(value=mixed)
 *  - func(callback)
 *  - string([strict])
 *  - int([strict])
 *  - float([strict])
 *  - array([strict])
 *  - bool([strict])
 *  - object([strict])
 */
class FilterService
{
    /**
     * FilterService constructor.
     *
     * @param  FilterFactory  $filterFactory
     */
    public function __construct(protected FilterFactory $filterFactory) {}

    public function filter(mixed $value, string|array $command): mixed
    {
        if (is_array($command)) {
            return $this->createNested($command)->filter($value);
        }

        return $this->createChain($command)->filter($value);
    }

    /**
     * validate
     *
     * @param  mixed         $value
     * @param  string|array  $command
     *
     * @return  bool
     *
     * @throws  ValidateException
     */
    public function validate(mixed $value, string|array $command, bool $strict = false): bool
    {
        if (is_array($command)) {
            return $this->createNested($command)->test($value, $strict);
        }

        return $this->createChain($command)->test($value, $strict);
    }

    /**
     * Filter a value with callback rules.
     *
     * A rule is one of:
     * - Closure: applied to the whole value.
     * - Non-empty list of Closures: applied in order as one chain.
     * - Associative array: maps field keys to rules, applied recursively. Missing keys are skipped.
     *
     * @param  mixed                $value
     * @param  \Closure|array       $rule
     *
     * @return  mixed
     */
    public function filterWith(mixed $value, \Closure|array $rule): mixed
    {
        if ($this->isCallbackRule($rule)) {
            foreach ($this->toCallbacks($rule) as $callback) {
                $value = $callback($value);
            }

            return $value;
        }

        if (!is_array($value)) {
            return $value;
        }

        foreach ($rule as $key => $subRule) {
            if (array_key_exists($key, $value)) {
                $value[$key] = $this->filterWith($value[$key], $subRule);
            }
        }

        return $value;
    }

    /**
     * Validate a value with callback rules. Every Closure in a chain receives the same value;
     * a Closure returning a falsy value fails the validation.
     *
     * @param  mixed           $value
     * @param  \Closure|array  $rule
     *
     * @return  bool
     *
     * @throws  ValidateException
     */
    public function validateWith(mixed $value, \Closure|array $rule): bool
    {
        $this->validateRule($value, $rule, '');

        return true;
    }

    /**
     * @throws  ValidateException
     */
    private function validateRule(mixed $value, \Closure|array $rule, string $path): void
    {
        if ($this->isCallbackRule($rule)) {
            foreach ($this->toCallbacks($rule) as $callback) {
                $validator = $this->createCallbackValidator($callback);

                if (!$validator->test($value)) {
                    throw ValidateException::create(
                        $validator,
                        sprintf(
                            '%s not match - Validator returns false, value is: %s',
                            $path === '' ? 'Value' : sprintf('Field "%s"', $path),
                            Assert::describeValue($value)
                        )
                    );
                }
            }

            return;
        }

        if (!is_array($value)) {
            return;
        }

        foreach ($rule as $key => $subRule) {
            if (array_key_exists($key, $value)) {
                $this->validateRule(
                    $value[$key],
                    $subRule,
                    $path === '' ? (string) $key : $path . '.' . $key
                );
            }
        }
    }

    private function createCallbackValidator(\Closure $callback): ValidatorInterface
    {
        return new readonly class ($callback) implements ValidatorInterface {
            public function __construct(private \Closure $callback) {}

            public function test(mixed $value, bool $strict = false): bool
            {
                return (bool) ($this->callback)($value);
            }
        };
    }

    private function isCallbackRule(mixed $rule): bool
    {
        if ($rule instanceof \Closure) {
            return true;
        }

        if (!is_array($rule) || $rule === [] || !array_is_list($rule)) {
            return false;
        }

        foreach ($rule as $item) {
            if (!$item instanceof \Closure) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return  \Closure[]
     */
    private function toCallbacks(\Closure|array $rule): array
    {
        return $rule instanceof \Closure ? [$rule] : $rule;
    }

    public function createNested(array $fields): NestedFilter
    {
        return $this->filterFactory->createNested($fields);
    }

    public function createChain(string $syntax): FilterInterface|ValidatorInterface
    {
        return $this->filterFactory->createChainFromSyntax($syntax);
    }

    public function addFilterFactory(string $type, callable|string $factory): static
    {
        $this->filterFactory->addFactory($type, $factory);

        return $this;
    }

    public function addFilterCallback(string $type, \Closure $callback): static
    {
        $this->filterFactory->addFilterCallback($type, $callback);

        return $this;
    }

    public function removeFilter(string $type): static
    {
        $this->filterFactory->removeFactory($type);

        return $this;
    }

    // @codeCoverageIgnoreStart
    public static function getInstance(): static
    {
        return Runtime::getContainer()->get(static::class);
    }
    // @codeCoverageIgnoreEnd
}
