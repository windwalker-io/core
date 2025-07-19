<?php

declare(strict_types=1);

namespace Windwalker\Core\Service;

use Windwalker\Core\Runtime\Runtime;
use Windwalker\Filter\Exception\ValidateException;
use Windwalker\Filter\FilterFactory;
use Windwalker\Filter\FilterInterface;
use Windwalker\Filter\NestedFilter;
use Windwalker\Filter\ValidatorInterface;

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
    public function __construct(protected FilterFactory $filterFactory)
    {
    }

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
     * @throws ValidateException
     */
    public function validate(mixed $value, string|array $command, bool $strict = false): bool
    {
        if (is_array($command)) {
            return $this->createNested($command)->test($value, $strict);
        }

        return $this->createChain($command)->test($value, $strict);
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

    public static function getInstance(): static
    {
        return Runtime::getContainer()->get(static::class);
    }
}
