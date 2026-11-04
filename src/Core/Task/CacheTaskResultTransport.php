<?php

declare(strict_types=1);

namespace Windwalker\Core\Task;

use InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;
use RuntimeException;
use UnexpectedValueException;

class CacheTaskResultTransport implements TaskResultTransportInterface
{
    public function __construct(private CacheItemPoolInterface $pool) {}

    public function publish(string $taskId, array $result, int $ttl): void
    {
        if ($ttl <= 0) {
            throw new InvalidArgumentException('Task result TTL must be positive.');
        }

        $item = $this->pool->getItem($taskId);
        $item->set($result)->expiresAfter($ttl);

        if (!$this->pool->save($item)) {
            throw new RuntimeException('Unable to publish task result.');
        }
    }

    public function receive(string $taskId): ?array
    {
        $item = $this->pool->getItem($taskId);

        if (!$item->isHit()) {
            return null;
        }

        $result = $item->get();

        if (!is_array($result)) {
            throw new UnexpectedValueException('Invalid cached task result.');
        }

        return $result;
    }

    public function delete(string $taskId): void
    {
        if (!$this->pool->deleteItem($taskId)) {
            throw new RuntimeException('Unable to delete task result.');
        }
    }
}
