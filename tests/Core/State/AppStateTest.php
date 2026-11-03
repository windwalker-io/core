<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\State;

use ArrayIterator;
use Iterator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Http\AppRequest;
use Windwalker\Core\State\AppState;
use Windwalker\Core\State\Persistence\CookiesPersistence;
use Windwalker\Core\State\Persistence\NullPersistence;
use Windwalker\Core\State\Persistence\PersistenceInterface;
use Windwalker\Core\State\Persistence\SessionPersistence;
use Windwalker\Data\Collection;
use Windwalker\DI\Container;
use Windwalker\Session\Cookie\CookiesInterface;
use Windwalker\Session\Session;
use Windwalker\Session\SessionInterface;

class AppStateTest extends TestCase
{
    private Container $container;

    private AppState $instance;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->container->share(SessionInterface::class, fn() => $this->createStub(SessionInterface::class));
        $this->instance = new AppState($this->container);
    }

    #[Test]
    public function getPersistDriverReturnsNullPersistenceWhenDriverIsFalse(): void
    {
        self::assertInstanceOf(NullPersistence::class, $this->instance->getPersistDriver(false));
    }

    #[Test]
    public function getPersistDriverReturnsSamePersistenceInstanceWhenAlreadyOne(): void
    {
        $persistence = $this->createStub(PersistenceInterface::class);

        self::assertSame($persistence, $this->instance->getPersistDriver($persistence));
    }

    #[Test]
    public function getPersistDriverDefaultsToSessionWhenNullOrTrue(): void
    {
        $session = $this->createStub(SessionInterface::class);
        $this->container->share(SessionInterface::class, fn() => $session);

        $driverFromNull = $this->instance->getPersistDriver(null);
        $driverFromTrue = $this->instance->getPersistDriver(true);

        self::assertInstanceOf(SessionPersistence::class, $driverFromNull);
        // Internally cached, so both calls return the same instance.
        self::assertSame($driverFromNull, $driverFromTrue);
    }

    #[Test]
    public function getPersistDriverResolvesSessionAliasString(): void
    {
        $session = $this->createStub(SessionInterface::class);
        $this->container->share(SessionInterface::class, fn() => $session);

        self::assertInstanceOf(SessionPersistence::class, $this->instance->getPersistDriver('session'));
    }

    #[Test]
    public function getPersistDriverResolvesCookieAliasString(): void
    {
        $cookies = $this->createStub(CookiesInterface::class);
        $this->container->share(CookiesInterface::class, fn() => $cookies);

        self::assertInstanceOf(CookiesPersistence::class, $this->instance->getPersistDriver('cookie'));
    }

    #[Test]
    public function getPersistDriverResolvesSessionClassFromObject(): void
    {
        $session = new Session();
        $this->container->share(Session::class, fn() => $session);

        self::assertInstanceOf(SessionPersistence::class, $this->instance->getPersistDriver($session));
    }

    #[Test]
    public function getPersistDriverResolvesArbitraryClassNameViaContainer(): void
    {
        $this->container->share(DriverStub::class, fn() => new DriverStub());

        self::assertInstanceOf(DriverStub::class, $this->instance->getPersistDriver(DriverStub::class));
    }

    #[Test]
    public function setAndGetStoreValueInMemoryState(): void
    {
        $this->instance->set('foo', 'bar');

        self::assertSame('bar', $this->instance->get('foo', false));
    }

    #[Test]
    public function getFallsBackToDriverWhenNotInMemoryState(): void
    {
        $driver = $this->createStub(PersistenceInterface::class);
        $driver->method('get')->willReturn('from-driver');

        self::assertSame('from-driver', $this->instance->get('foo', $driver));
    }

    #[Test]
    public function getAndForgetReturnsStoredValue(): void
    {
        // Note: getAndForget() calls forget() without forwarding $driver, so
        // only the default (session) driver is cleared; the in-memory state
        // set via set() is not removed. We only verify the returned value.
        $this->instance->set('foo', 'bar');

        self::assertSame('bar', $this->instance->getAndForget('foo', false));
    }

    #[Test]
    public function rememberStoresValueOnDriverAndReturnsIt(): void
    {
        $driver = $this->createMock(PersistenceInterface::class);
        $driver->expects($this->once())->method('store')->with('foo', 'bar');

        self::assertSame('bar', $this->instance->remember('foo', 'bar', $driver));
    }

    #[Test]
    public function rememberThenGetRoundTripsThroughDriver(): void
    {
        $driver = new ArrayPersistenceStub();

        $this->instance->remember('foo', 'bar', $driver);

        self::assertSame('bar', $this->instance->get('foo', $driver));
    }

    #[Test]
    public function hasReturnsTrueWhenDriverHasValue(): void
    {
        $driver = $this->createStub(PersistenceInterface::class);
        $driver->method('get')->willReturn('bar');

        self::assertTrue($this->instance->has('foo', $driver));
    }

    #[Test]
    public function hasReturnsTrueWhenInMemoryStateHasValueButDriverDoesNot(): void
    {
        $this->instance->set('foo', 'bar');

        self::assertTrue($this->instance->has('foo', false));
    }

    #[Test]
    public function hasReturnsFalseWhenNeitherDriverNorStateHasValue(): void
    {
        self::assertFalse($this->instance->has('missing', false));
    }

    #[Test]
    public function forgetDelegatesToDriver(): void
    {
        $driver = $this->createMock(PersistenceInterface::class);
        $driver->expects($this->once())->method('forget')->with('foo');

        self::assertSame($this->instance, $this->instance->forget('foo', $driver));
    }

    #[Test]
    public function rememberFromRequestStoresInputValueWhenPresent(): void
    {
        $driver = new ArrayPersistenceStub();

        $request = $this->createStub(AppRequest::class);
        $request->method('input')->willReturn('from-request');

        $this->container->share(AppRequest::class, fn() => $request);

        self::assertSame('from-request', $this->instance->rememberFromRequest('foo', driver: $driver));
        self::assertSame('from-request', $this->instance->get('foo', $driver));
    }

    #[Test]
    public function rememberFromRequestFallsBackToGetWhenInputIsMissing(): void
    {
        $this->instance->set('foo', 'existing');

        $request = $this->createStub(AppRequest::class);
        $request->method('input')->willReturn(null);

        $this->container->share(AppRequest::class, fn() => $request);

        self::assertSame('existing', $this->instance->rememberFromRequest('foo', driver: false));
    }

    #[Test]
    public function rememberMergeRequestMergesArraysWithRemembered(): void
    {
        $driver = new ArrayPersistenceStub();

        $this->instance->remember('foo', ['a' => 1], $driver);

        $request = $this->createStub(AppRequest::class);
        $request->method('input')->willReturn(['b' => 2]);

        $this->container->share(AppRequest::class, fn() => $request);

        self::assertSame(['a' => 1, 'b' => 2], $this->instance->rememberMergeRequest('foo', driver: $driver));
    }

    #[Test]
    public function rememberMergeRequestFallsBackToGetWhenInputIsMissing(): void
    {
        $this->instance->set('foo', 'existing');

        $request = $this->createStub(AppRequest::class);
        $request->method('input')->willReturn(null);

        $this->container->share(AppRequest::class, fn() => $request);

        self::assertSame('existing', $this->instance->rememberMergeRequest('foo', driver: false));
    }

    #[Test]
    public function allYieldsDriverValuesFollowedByInMemoryState(): void
    {
        $this->instance->set('foo', 'bar');

        $driver = $this->createStub(PersistenceInterface::class);
        $driver->method('all')->willReturn(new ArrayIterator(['baz' => 'qux']));

        self::assertSame(
            ['baz' => 'qux', 'foo' => 'bar'],
            iterator_to_array($this->instance->all($driver))
        );
    }

    #[Test]
    public function allFiltersByPrefixWhenSet(): void
    {
        $prefixed = $this->instance->withPrefix('ns');
        $prefixed->set('foo', 'bar');

        $driver = $this->createStub(PersistenceInterface::class);
        $driver->method('all')->willReturn(
            new ArrayIterator(['other.key' => 'ignored', 'ns.baz' => 'from-driver'])
        );

        self::assertSame(
            ['ns.baz' => 'from-driver', 'ns.foo' => 'bar'],
            iterator_to_array($prefixed->all($driver))
        );
    }

    #[Test]
    public function dumpReturnsArrayOfAllValues(): void
    {
        $this->instance->set('foo', 'bar');

        self::assertSame(['foo' => 'bar'], $this->instance->dump(false));
    }

    #[Test]
    public function jsonSerializeReturnsAllValues(): void
    {
        $this->instance->set('foo', 'bar');

        self::assertSame(['foo' => 'bar'], $this->instance->jsonSerialize());
    }

    #[Test]
    public function withPrefixReturnsNewInstanceWithPrefixSetAndKeepsOriginalUnprefixed(): void
    {
        $prefixed = $this->instance->withPrefix('ns');

        self::assertNotSame($this->instance, $prefixed);
        self::assertSame('', $this->instance->getPrefix());
        self::assertSame('ns', $prefixed->getPrefix());
    }

    #[Test]
    public function withPrefixScopesSetAndGetUnderPrefixedKey(): void
    {
        $prefixed = $this->instance->withPrefix('ns');
        $prefixed->set('foo', 'bar');

        self::assertSame('bar', $prefixed->get('foo', false));
        self::assertSame(['ns.foo' => 'bar'], iterator_to_array($prefixed->getState()));
    }

    #[Test]
    public function getStateAndSetStateExposeUnderlyingCollection(): void
    {
        $collection = new Collection(['a' => 1]);

        $this->instance->setState($collection);

        self::assertSame($collection, $this->instance->getState());
    }
}

/**
 * Minimal persistence double that actually stores values in an array, used to
 * verify round-trip behavior (remember()/get()/rememberMergeRequest()) that
 * cannot be observed through a pure stub/mock.
 */
class ArrayPersistenceStub implements PersistenceInterface
{
    private array $storage = [];

    public function get(string $key): mixed
    {
        return $this->storage[$key] ?? null;
    }

    public function store(string $key, mixed $value): mixed
    {
        return $this->storage[$key] = $value;
    }

    public function forget(string $key): void
    {
        unset($this->storage[$key]);
    }

    public function all(): Iterator
    {
        return new ArrayIterator($this->storage);
    }
}

/**
 * Minimal persistence double used only to verify
 * {@see AppState::getPersistDriver()} resolves arbitrary class-name strings
 * through the container.
 */
class DriverStub implements PersistenceInterface
{
    public function get(string $key): mixed
    {
        return null;
    }

    public function store(string $key, mixed $value): mixed
    {
        return $value;
    }

    public function forget(string $key): void {}

    public function all(): Iterator
    {
        return new ArrayIterator([]);
    }
}
