<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

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
    public function validate(mixed $value, string|array $command): bool
    {
        if (is_array($command)) {
            return $this->createNested($command)->test($value);
        }

        return $this->createChain($command)->test($value);
    }

    public function createNested(array $fields): NestedFilter
    {
        return $this->filterFactory->createNested($fields);
    }

    public function createChain(string $syntax): FilterInterface|ValidatorInterface
    {
        return $this->filterFactory->createChainFromSyntax($syntax);
    }

    public static function getInstance(): static
    {
        return Runtime::getContainer()->get(static::class);
    }
}
