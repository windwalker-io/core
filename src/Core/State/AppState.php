<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\State;

use Windwalker\Core\Http\AppRequest;
use Windwalker\Core\State\Persistence\CookiesPersistence;
use Windwalker\Core\State\Persistence\NullPersistence;
use Windwalker\Core\State\Persistence\PersistenceInterface;
use Windwalker\Core\State\Persistence\SessionPersistence;
use Windwalker\Data\Collection;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\DI\Container;
use Windwalker\Session\Cookie\Cookies;
use Windwalker\Session\Cookie\CookiesInterface;
use Windwalker\Session\Session;
use Windwalker\Session\SessionInterface;
use Windwalker\Utilities\Cache\InstanceCacheTrait;
use Windwalker\Utilities\Iterator\UniqueIterator;

/**
 * The UserState class.
 */
class AppState implements \JsonSerializable
{
    use InstanceCacheTrait;

    protected Collection $state;

    protected string $prefix = '';

    /**
     * UserState constructor.
     *
     * @param  Container   $container
     */
    public function __construct(
        protected Container $container
    ) {
        $this->state = new Collection();
    }

    protected function getKeyName(string $key): string
    {
        return $this->prefix . '.' . $key;
    }

    protected function resolvePersistDriver(mixed $driverName): PersistenceInterface
    {
        if ($driverName === false) {
            return new NullPersistence();
        }

        if ($driverName instanceof PersistenceInterface) {
            return $driverName;
        }

        if ($driverName === null || $driverName === true) {
            $driverName = SessionInterface::class;
        }

        if (is_object($driverName)) {
            $driverName = $driverName::class;
        }

        if (is_string($driverName)) {
            $driverName = match ($driverName) {
                'session' => SessionInterface::class,
                'cookie' => CookiesInterface::class,
                'database' => DatabaseAdapter::class,
                default => $driverName
            };

            return match ($driverName) {
                Session::class, SessionInterface::class
                => $this->cacheStorage['session'] ??= new SessionPersistence($this->container->get($driverName)),
                Cookies::class, CookiesInterface::class
                => $this->cacheStorage['cookies'] ??= new CookiesPersistence($this->container->get($driverName)),
                default => $this->container->resolve($driverName)
            };
        }

        return $driverName;
    }

    /**
     * @inheritDoc
     */
    public function &get(string $key, mixed $driver = null): mixed
    {
        $key    = $this->getKeyName($key);
        $driver = $this->resolvePersistDriver($driver);
        $value  = $driver->get($key) ?? $this->state->get($key);

        return $value;
    }

    public function getAndForget(string $key, mixed $driver = null): mixed
    {
        $value = $this->get($key, $driver);

        $this->forget($key);

        return $value;
    }

    public function rememberFromRequest(
        string $key,
        ?string $inputField = null,
        mixed $driver = null
    ): mixed {
        $inputField ??= $key;

        $key    = $this->getKeyName($key);
        $driver = $this->resolvePersistDriver($driver);

        $inputValue = $this->getRequest()->input($inputField);

        if ($inputValue === null) {
            return $this->get($key, $driver);
        }

        return $this->remember($key, $inputValue, $driver);
    }

    public function remember(string $key, mixed $value, mixed $driver = null): mixed
    {
        $key    = $this->getKeyName($key);
        $driver = $this->resolvePersistDriver($driver);

        $driver->store($key, $value);

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value): static
    {
        $this->state->set($this->getKeyName($key), $value);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key, mixed $driver = null): bool
    {
        $key    = $this->getKeyName($key);
        $driver = $this->resolvePersistDriver($driver);
        $value  = $driver->get($key);

        if ($value !== null) {
            return true;
        }

        return $this->state->has($key);
    }

    public function forget(string $key, mixed $driver = null): static
    {
        $key    = $this->getKeyName($key);
        $driver = $this->resolvePersistDriver($driver);

        $driver->forget($key);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function all(mixed $driver = null): \Generator
    {
        $driver = $this->resolvePersistDriver($driver);

        $iter = new \AppendIterator();
        $iter->append($this->state->getIterator());
        $iter->append($driver->all());

        foreach (new UniqueIterator($iter) as $key => $item) {
            if (str_starts_with($key, $this->prefix . '.')) {
                yield $key => $item;
            }
        }
    }

    public function dump(mixed $driver = null): array
    {
        return iterator_to_array($this->all($driver));
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
        return $this->request ??= $this->container->get(AppRequest::class);
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
        $new         = clone $this;
        $new->prefix = $prefix;

        return $new;
    }

    /**
     * @return Collection
     */
    public function getState(): Collection
    {
        return $this->state;
    }

    /**
     * @param  Collection  $state
     *
     * @return  static  Return self to support chaining.
     */
    public function setState(Collection $state): static
    {
        $this->state = $state;

        return $this;
    }
}
