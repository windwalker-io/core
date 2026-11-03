<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Queue;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Queue\QueueFailerFactory;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\Container;
use Windwalker\Tests\Core\Queue\Stub\StubQueueFailer;

class QueueFailerFactoryTest extends TestCase
{
    protected function createFactory(array $data = []): QueueFailerFactory
    {
        return new QueueFailerFactory(new Config(['queue' => $data]), new Container());
    }

    #[Test]
    public function getConfigPrefixReturnsQueue(): void
    {
        self::assertSame('queue', $this->createFactory()->getConfigPrefix());
    }

    #[Test]
    public function getDefaultNameReturnsConfiguredFailerDefault(): void
    {
        $factory = $this->createFactory(['failer_default' => 'foo']);

        self::assertSame('foo', $factory->getDefaultName());
    }

    #[Test]
    public function getDefaultNameReturnsNullWhenNotConfigured(): void
    {
        self::assertNull($this->createFactory()->getDefaultName());
    }

    #[Test]
    public function hasReturnsTrueWhenFailerDefinitionExists(): void
    {
        $factory = $this->createFactory(
            [
                'factories' => [
                    'failers' => [
                        'foo' => StubQueueFailer::class,
                    ],
                ],
            ]
        );

        self::assertTrue($factory->has('foo'));
    }

    #[Test]
    public function hasReturnsFalseWhenFailerDefinitionMissing(): void
    {
        self::assertFalse($this->createFactory()->has('foo'));
    }

    #[Test]
    public function hasIgnoresInstancesPathAndOnlyChecksFailersPath(): void
    {
        $factory = $this->createFactory(
            [
                'factories' => [
                    'instances' => [
                        'foo' => StubQueueFailer::class,
                    ],
                ],
            ]
        );

        self::assertFalse($factory->has('foo'));
    }

    #[Test]
    public function createBuildsFailerInstance(): void
    {
        $factory = $this->createFactory(
            [
                'factories' => [
                    'failers' => [
                        'foo' => StubQueueFailer::class,
                    ],
                ],
            ]
        );

        $instance = $factory->create('foo');

        self::assertInstanceOf(StubQueueFailer::class, $instance);
    }

    #[Test]
    public function createUsesDefaultNameWhenNameOmitted(): void
    {
        $factory = $this->createFactory(
            [
                'failer_default' => 'foo',
                'factories' => [
                    'failers' => [
                        'foo' => StubQueueFailer::class,
                    ],
                ],
            ]
        );

        self::assertInstanceOf(StubQueueFailer::class, $factory->create());
    }

    #[Test]
    public function getCachesInstanceAcrossCalls(): void
    {
        $factory = $this->createFactory(
            [
                'factories' => [
                    'failers' => [
                        'foo' => StubQueueFailer::class,
                    ],
                ],
            ]
        );

        self::assertSame($factory->get('foo'), $factory->get('foo'));
    }
}
