<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\State\Persistence;

use ArrayIterator;
use Iterator;

/**
 * The NullPersistence class.
 */
class NullPersistence implements PersistenceInterface
{
    public function get(string $key): mixed
    {
        return null;
    }

    public function store(string $key, mixed $value): mixed
    {
        return $value;
    }

    public function forget(string $key): void
    {
    }

    public function all(): Iterator
    {
        return new ArrayIterator([]);
    }
}
