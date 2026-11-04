<?php

declare(strict_types=1);

namespace Windwalker\Core\Task;

interface TaskResultTransportInterface
{
    public function publish(string $taskId, array $result, int $ttl): void;

    public function receive(string $taskId): ?array;

    public function delete(string $taskId): void;
}
