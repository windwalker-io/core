<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\State\Persistence;

use Windwalker\Session\SessionInterface;

/**
 * The SessionPersistence class.
 */
class SessionPersistence implements PersistenceInterface
{
    /**
     * SessionPersistence constructor.
     *
     * @param  SessionInterface  $session
     */
    public function __construct(protected SessionInterface $session)
    {
    }

    public function get(string $key): mixed
    {
        return $this->session->get($key);
    }

    public function store(string $key, mixed $value): mixed
    {
        $this->session->set($key, $value);

        return $value;
    }

    public function forget(string $key): void
    {
        $this->session->remove($key);
    }

    public function all(): \Iterator
    {
        return new \ArrayIterator($this->session->dump());
    }
}
