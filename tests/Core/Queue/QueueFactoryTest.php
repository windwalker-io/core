<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Queue;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Queue\QueueFactory;
use Windwalker\Core\Runtime\Config;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\DI\Container;
use Windwalker\Queue\Driver\DatabaseQueueDriver;
use Windwalker\Queue\Driver\SyncQueueDriver;
use Windwalker\Queue\Enum\DatabaseIdType;
use Windwalker\Queue\Queue;
use Windwalker\Queue\QueueMessage;
use Windwalker\Tests\Core\Queue\Stub\StubQueueDriver;

class QueueFactoryTest extends TestCase
{
    protected function createFactory(array $data = []): QueueFactory
    {
        return new QueueFactory(new Config(['queue' => $data]), new Container());
    }

    #[Test]
    public function getClassNameReturnsQueueClass(): void
    {
        self::assertSame(Queue::class, $this->createFactory()->getClassName());
    }

    #[Test]
    public function getConfigPrefixReturnsQueue(): void
    {
        self::assertSame('queue', $this->createFactory()->getConfigPrefix());
    }

    #[Test]
    public function createBuildsInstanceAndOverridesObjectBuilderToUseContainerResolve(): void
    {
        $factory = $this->createFactory(
            [
                'factories' => [
                    'instances' => [
                        'foo' => static fn() => new Queue(new StubQueueDriver()),
                    ],
                ],
            ]
        );

        $queue = $factory->create('foo');

        self::assertInstanceOf(Queue::class, $queue);

        // The overridden builder must delegate to Container::resolve(), autowiring
        // constructor args instead of plain `new $class(...$args)`.
        $built = $queue->getObjectBuilder()->createObject(StubQueueDriver::class, args: ['y' => 2]);

        self::assertInstanceOf(StubQueueDriver::class, $built);
        self::assertSame(['y' => 2], $built->args);
    }

    #[Test]
    public function createQueueBuildsQueueWrappingGivenDriverClass(): void
    {
        $factory = $this->createFactory();

        $queue = $factory->createQueue(StubQueueDriver::class, args: ['x' => 1]);

        self::assertInstanceOf(Queue::class, $queue);
        self::assertInstanceOf(StubQueueDriver::class, $queue->getDriver());
        self::assertSame(['x' => 1], $queue->getDriver()->args);
    }

    #[Test]
    public function syncAdapterReturnsClosureBuildingQueueWithSyncQueueDriver(): void
    {
        $handler = static fn(QueueMessage $message) => 'handled';

        $closure = QueueFactory::syncAdapter($handler, ['debug' => true]);

        $queue = $closure(new Container());

        self::assertInstanceOf(Queue::class, $queue);

        /** @var SyncQueueDriver $driver */
        $driver = $queue->getDriver();

        self::assertInstanceOf(SyncQueueDriver::class, $driver);
        self::assertSame($handler, $driver->getHandler());
        self::assertTrue($driver->getOption('debug'));
    }

    #[Test]
    public function databaseAdapterReturnsClosureBuildingQueueWithDatabaseQueueDriver(): void
    {
        $db = $this->createStub(DatabaseAdapter::class);

        $closure = QueueFactory::databaseAdapter($db, 'my-channel', 'my_table', 30, DatabaseIdType::UUID);

        $queue = $closure(new Container());

        /** @var DatabaseQueueDriver $driver */
        $driver = $queue->getDriver();

        self::assertInstanceOf(DatabaseQueueDriver::class, $driver);

        $ref = new \ReflectionObject($driver);
        self::assertSame($db, $ref->getProperty('db')->getValue($driver));
        self::assertSame('my-channel', $ref->getProperty('channel')->getValue($driver));
        self::assertSame('my_table', $ref->getProperty('table')->getValue($driver));
        self::assertSame(30, $ref->getProperty('timeout')->getValue($driver));
    }

    #[Test]
    public function createContainerHandlerRunsJobThroughContainerCall(): void
    {
        $factory = $this->createFactory();

        $called = false;
        $job = static function () use (&$called) {
            $called = true;
        };

        $message = new QueueMessage($job);

        $controller = ($factory->createContainerHandler())($message);

        self::assertTrue($called);
        self::assertFalse($controller->failed);
    }
}
