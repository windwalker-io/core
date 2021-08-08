<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\State\Persistence;

/**
 * Interface PersistenceInterface
 */
interface PersistenceInterface
{
    public function get(string $key): mixed;

    public function store(string $key, mixed $value): mixed;

    public function forget(string $key): void;

    public function all(): \Iterator;
}
