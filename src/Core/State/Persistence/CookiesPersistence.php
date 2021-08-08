<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\State\Persistence;

use Windwalker\Session\Cookie\CookiesInterface;

/**
 * The CookiesPersistence class.
 */
class CookiesPersistence implements PersistenceInterface
{
    /**
     * CookiesPersistence constructor.
     *
     * @param  CookiesInterface  $cookies
     */
    public function __construct(protected CookiesInterface $cookies)
    {
    }

    public function get(string $key): mixed
    {
        return $this->cookies->get($key);
    }

    public function store(string $key, mixed $value): mixed
    {
        $this->cookies->set($key, $value);

        return $value;
    }

    public function forget(string $key): void
    {
        $this->cookies->remove($key);
    }

    public function all(): \Iterator
    {
        return new \ArrayIterator($this->cookies->getStorage());
    }
}
