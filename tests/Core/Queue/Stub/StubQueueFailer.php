<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Queue\Stub;

use Windwalker\Queue\Failer\QueueFailerInterface;

/**
 * Minimal no-op QueueFailerInterface implementation used as a concrete,
 * instantiable `factories.failers.*` target in QueueFailerFactory tests.
 */
class StubQueueFailer implements QueueFailerInterface
{
    /**
     * @var array<array{string, string, string, string}>
     */
    public array $added = [];

    public function add(string $connection, string $channel, string $body, string $exception): int|string
    {
        $this->added[] = [$connection, $channel, $body, $exception];

        return 1;
    }

    public function all(): iterable
    {
        return [];
    }

    public function get(mixed $conditions): ?array
    {
        return null;
    }

    public function remove(mixed $conditions): bool
    {
        return true;
    }

    public function clear(): bool
    {
        return true;
    }

    public function size(): int
    {
        return 0;
    }
}
