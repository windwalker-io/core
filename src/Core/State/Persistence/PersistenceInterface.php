<?php

declare(strict_types=1);

namespace Windwalker\Core\State\Persistence;

use Iterator;

/**
 * Interface PersistenceInterface
 */
interface PersistenceInterface
{
    public function get(string $key): mixed;

    public function store(string $key, mixed $value): mixed;

    public function forget(string $key): void;

    public function all(): Iterator;
}
