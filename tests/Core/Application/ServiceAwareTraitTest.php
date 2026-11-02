<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application;

use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\DI\Container;
use Windwalker\Tests\Core\Application\Stub\CounterService;
use Windwalker\Tests\Core\Application\Stub\ServiceAwareStub;

class ServiceAwareTraitTest extends TestCase
{
    protected ServiceAwareStub $instance;

    protected Container $container;

    #[Before]
    protected function setUpInstance(): void
    {
        $this->container = new Container();
        $this->instance = new ServiceAwareStub()->setContainer($this->container);
    }

    #[Test]
    public function getContainerCreatesOneLazilyWhenNotSet(): void
    {
        $stub = new ServiceAwareStub();

        self::assertInstanceOf(Container::class, $stub->getContainer());
        self::assertSame($stub->getContainer(), $stub->getContainer());
    }

    #[Test]
    public function retrieveReturnsSharedInstanceAndSupportsForceNew(): void
    {
        $this->container->share(CounterService::class, new CounterService(1));

        $a = $this->instance->retrieve(CounterService::class);

        self::assertSame($a, $this->instance->retrieve(CounterService::class));
        self::assertSame(1, $a->count);
    }

    #[Test]
    public function hasServiceReflectsContainerState(): void
    {
        self::assertFalse($this->instance->hasService('foo'));

        $this->instance->bind('foo', fn() => 'bar');

        self::assertTrue($this->instance->hasService('foo'));
    }

    #[Test]
    public function makeAlwaysCreatesNewInstanceWithArgs(): void
    {
        $a = $this->instance->make(CounterService::class, ['count' => 5]);
        $b = $this->instance->make(CounterService::class);

        self::assertNotSame($a, $b);
        self::assertSame(5, $a->count);
        self::assertSame(0, $b->count);
    }

    #[Test]
    public function serviceCreatesSharedObjectOnceThenReusesIt(): void
    {
        $a = $this->instance->service(CounterService::class, ['count' => 3]);
        $b = $this->instance->service(CounterService::class, ['count' => 9]);

        self::assertSame($a, $b);
        self::assertSame(3, $a->count);
        self::assertTrue($this->instance->hasService(CounterService::class));
    }

    #[Test]
    public function serviceSeparatesObjectsByTag(): void
    {
        $a = $this->instance->service(CounterService::class, ['count' => 1], tag: 'a');
        $b = $this->instance->service(CounterService::class, ['count' => 2], tag: 'b');

        self::assertNotSame($a, $b);
        self::assertSame($a, $this->instance->service(CounterService::class, tag: 'a'));
    }

    #[Test]
    public function callInvokesCallableWithInjectedArgs(): void
    {
        $this->container->share(CounterService::class, new CounterService(4));

        $result = $this->instance->call(
            static fn(CounterService $s, int $add) => $s->count + $add,
            ['add' => 6]
        );

        self::assertSame(10, $result);
    }

    #[Test]
    public function bindAndResolveWork(): void
    {
        $this->instance->bind('answer', fn() => 42);

        self::assertSame(42, $this->instance->resolve('answer'));
        self::assertInstanceOf(CounterService::class, $this->instance->resolve(CounterService::class));
    }
}
