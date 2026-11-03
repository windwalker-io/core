<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\DI;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Manager\Event\InstanceCreatedEvent;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\Container;
use Windwalker\Tests\Core\DI\Stub\StubClassScopedServiceFactory;
use Windwalker\Tests\Core\DI\Stub\StubCreatedService;
use Windwalker\Tests\Core\DI\Stub\StubServiceFactory;

class ServiceFactoryTraitTest extends TestCase
{
    protected function createFactory(array $data = []): StubServiceFactory
    {
        return new StubServiceFactory(new Config($data), new Container());
    }

    #[Test]
    public function getDefaultNameReturnsConfigDefaultValue(): void
    {
        $factory = $this->createFactory(['default' => 'foo']);

        self::assertSame('foo', $factory->getDefaultName());
    }

    #[Test]
    public function getDefaultNameReturnsNullWhenNoDefaultConfigured(): void
    {
        $factory = $this->createFactory();

        self::assertNull($factory->getDefaultName());
    }

    #[Test]
    public function hasReturnsTrueWhenFactoryDefinitionExists(): void
    {
        $factory = $this->createFactory(
            [
                'factories' => [
                    'instances' => [
                        'foo' => StubCreatedService::class,
                    ],
                ],
            ]
        );

        self::assertTrue($factory->has('foo'));
    }

    #[Test]
    public function hasReturnsFalseWhenFactoryDefinitionMissing(): void
    {
        $factory = $this->createFactory();

        self::assertFalse($factory->has('foo'));
    }

    #[Test]
    public function hasUsesDefaultNameWhenNameOmitted(): void
    {
        $factory = $this->createFactory(
            [
                'default' => 'foo',
                'factories' => [
                    'instances' => [
                        'foo' => StubCreatedService::class,
                    ],
                ],
            ]
        );

        self::assertTrue($factory->has());
    }

    #[Test]
    public function hasThrowsExceptionWhenNameAndDefaultNameAreNull(): void
    {
        $factory = $this->createFactory();

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessageIsOrContains('Empty definition name.');

        $factory->has();
    }

    #[Test]
    public function createBuildsInstanceAndPassesInstanceNameAndTagArgs(): void
    {
        $factory = $this->createFactory(
            [
                'factories' => [
                    'instances' => [
                        'foo' => StubCreatedService::class,
                    ],
                ],
            ]
        );

        $instance = $factory->create('foo');

        self::assertInstanceOf(StubCreatedService::class, $instance);
        self::assertSame('foo', $instance->instanceName);
        self::assertSame('foo', $instance->tag);
    }

    #[Test]
    public function createEmitsInstanceCreatedEvent(): void
    {
        $factory = $this->createFactory(
            [
                'factories' => [
                    'instances' => [
                        'foo' => StubCreatedService::class,
                    ],
                ],
            ]
        );

        $captured = null;
        $factory->on(
            InstanceCreatedEvent::class,
            function (InstanceCreatedEvent $event) use (&$captured) {
                $captured = $event;
            }
        );

        $instance = $factory->create('foo');

        self::assertInstanceOf(InstanceCreatedEvent::class, $captured);
        self::assertSame($instance, $captured->instance);
        self::assertSame('foo', $captured->instanceName);
    }

    #[Test]
    public function createUsesDefaultNameWhenNameOmitted(): void
    {
        $factory = $this->createFactory(
            [
                'default' => 'foo',
                'factories' => [
                    'instances' => [
                        'foo' => StubCreatedService::class,
                    ],
                ],
            ]
        );

        $instance = $factory->create();

        self::assertSame('foo', $instance->instanceName);
    }

    #[Test]
    public function createThrowsExceptionWhenNameAndDefaultNameAreNull(): void
    {
        $factory = $this->createFactory();

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessageIsOrContains('Empty definition name.');

        $factory->create();
    }

    #[Test]
    public function createThrowsExceptionWhenDefinitionNotFound(): void
    {
        $factory = $this->createFactory();

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessageIsOrContains('definition: "foo" not found');

        $factory->create('foo');
    }

    #[Test]
    public function getCachesInstanceAcrossCalls(): void
    {
        $factory = $this->createFactory(
            [
                'factories' => [
                    'instances' => [
                        'foo' => StubCreatedService::class,
                    ],
                ],
            ]
        );

        $first = $factory->get('foo');
        $second = $factory->get('foo');

        self::assertSame($first, $second);
    }

    #[Test]
    public function configReturnsNestedValueUsingDelimiter(): void
    {
        $factory = $this->createFactory(['foo' => ['bar' => 'baz']]);

        self::assertSame('baz', $factory->config('foo.bar'));
        self::assertNull($factory->config('foo.missing'));
    }

    #[Test]
    public function getClassNameDefaultsToNull(): void
    {
        $factory = $this->createFactory();

        self::assertNull($factory->getClassName());
    }

    #[Test]
    public function getFactoryPathUsesClassSpecificPathWhenConfigured(): void
    {
        $config = new Config(
            [
                'factories' => [
                    StubCreatedService::class => [
                        'foo' => StubCreatedService::class,
                    ],
                    'instances' => [
                        'foo' => 'should-not-be-used',
                    ],
                ],
            ]
        );
        $factory = new StubClassScopedServiceFactory($config, new Container(), StubCreatedService::class);

        $instance = $factory->create('foo');

        self::assertInstanceOf(StubCreatedService::class, $instance);
    }

    #[Test]
    public function getFactoryPathFallsBackToInstancesWhenClassSpecificMissing(): void
    {
        $config = new Config(
            [
                'factories' => [
                    'instances' => [
                        'foo' => StubCreatedService::class,
                    ],
                ],
            ]
        );
        $factory = new StubClassScopedServiceFactory($config, new Container(), StubCreatedService::class);

        $instance = $factory->create('foo');

        self::assertInstanceOf(StubCreatedService::class, $instance);
    }
}
