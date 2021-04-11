<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Session;

use Windwalker\Core\Http\AppRequest;
use Windwalker\Session\Session;
use Windwalker\Session\SessionInterface;

/**
 * The UserState class.
 */
class UserState implements \JsonSerializable, \Countable
{
    /**
     * UserState constructor.
     */
    public function __construct(
        protected string $prefix,
        protected Session $session,
        protected AppRequest $request
    ) {
    }

    protected function getKeyName(string $key): string
    {
        return $this->prefix . '.' . $key;
    }

    /**
     * @inheritDoc
     */
    public function &get(string $key): mixed
    {
        return $this->session->get($this->getKeyName($key));
    }

    public function getFromRequest(string $key, ?string $inputField = null): mixed
    {
        $inputField ??= $key;

        $key = $this->getKeyName($key);

        return $this->session->overrideWith($key, $this->request->input($inputField));
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value): static
    {
        $this->session->set($this->getKeyName($key), $value);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function def(string $key, mixed $default): mixed
    {
        return $this->session->def($this->getKeyName($key), $default);
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return $this->session->has($this->getKeyName($key));
    }

    /**
     * @inheritDoc
     */
    public function all(): \Generator
    {
        foreach ($this->session->dump() as $key => $item) {
            if (str_starts_with($key, $this->prefix . '.')) {
                yield $key => $item;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return iterator_count($this->all());
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return iterator_to_array($this->all());
    }

    /**
     * @return AppRequest
     */
    public function getRequest(): AppRequest
    {
        return $this->request;
    }

    /**
     * @return SessionInterface
     */
    public function getSession(): SessionInterface
    {
        return $this->session;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @param  string  $prefix
     *
     * @return  static  Return self to support chaining.
     */
    public function withPrefix(string $prefix): static
    {
        $new = clone $this;
        $new->prefix = $prefix;

        return $new;
    }
}
