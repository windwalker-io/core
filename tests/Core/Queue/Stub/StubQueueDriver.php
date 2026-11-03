<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Queue\Stub;

use Windwalker\Queue\Driver\QueueDriverInterface;
use Windwalker\Queue\QueueMessage;

/**
 * Minimal no-op QueueDriverInterface implementation used as a `Queue` driver
 * in Queue factory/worker tests, so no real queue backend is required.
 */
class StubQueueDriver implements QueueDriverInterface
{
    public function __construct(public array $args = []) {}

    public function push(QueueMessage $message): string
    {
        return '1';
    }

    public function pop(?string $channel = null): ?QueueMessage
    {
        return null;
    }

    public function delete(QueueMessage $message): static
    {
        return $this;
    }

    public function release(QueueMessage $message): static
    {
        return $this;
    }

    public function defer(QueueMessage $message): static
    {
        return $this;
    }

    public function size(?string $channel = null): int
    {
        return 0;
    }
}
