<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Task;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Windwalker\Cache\CachePool;
use Windwalker\Cache\Storage\ArrayStorage;
use Windwalker\Core\Task\CacheTaskResultTransport;
use Windwalker\Core\Task\CallbackTaskResultTransport;

class TaskResultTransportTest extends TestCase
{
    #[Test]
    public function cacheRoundTripDeletionAndExpiration(): void
    {
        $pool = new CachePool(new ArrayStorage());
        $transport = new CacheTaskResultTransport($pool);
        $transport->publish('task', ['value' => null], 300);
        self::assertSame(['value' => null], $transport->receive('task'));
        $transport->delete('task');
        self::assertNull($transport->receive('task'));

        $item = $pool->getItem('expired')->set(['value' => 1]);
        $item->expiresAt(new \DateTimeImmutable('-1 second'));
        $pool->save($item);
        self::assertNull($transport->receive('expired'));
    }

    #[Test]
    public function cacheWriteFailuresAreExplicit(): void
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $pool->expects($this->once())->method('getItem')->with('task')
            ->willReturn(new \Windwalker\Cache\CachePool()->getItem('task'));
        $pool->expects($this->once())->method('save')->willReturn(false);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to publish task result.');
        new CacheTaskResultTransport($pool)->publish('task', [], 300);
    }

    #[Test]
    public function cacheDeleteFailuresAreExplicit(): void
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $pool->expects($this->once())->method('deleteItem')->with('task')->willReturn(false);
        $this->expectException(\RuntimeException::class);
        new CacheTaskResultTransport($pool)->delete('task');
    }

    #[Test]
    public function callbackAdapterForwardsIdentityResultAndTtl(): void
    {
        $published = null;
        $deleted = null;
        $transport = new CallbackTaskResultTransport(
            static function (string $id, array $result, int $ttl) use (&$published): void {
                $published = [$id, $result, $ttl];
            },
            static fn(string $id): array => ['id' => $id],
            static function (string $id) use (&$deleted): void {
                $deleted = $id;
            }
        );
        $transport->publish('task', ['value' => 42], 123);
        self::assertSame(['task', ['value' => 42], 123], $published);
        self::assertSame(['id' => 'task'], $transport->receive('task'));
        $transport->delete('task');
        self::assertSame('task', $deleted);
    }
}
