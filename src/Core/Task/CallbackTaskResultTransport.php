<?php

declare(strict_types=1);

namespace Windwalker\Core\Task;

use Closure;

class CallbackTaskResultTransport implements TaskResultTransportInterface
{
    public function __construct(
        private Closure $publish,
        private Closure $receive,
        private Closure $delete
    ) {}

    public function publish(string $taskId, array $result, int $ttl): void
    {
        ($this->publish)($taskId, $result, $ttl);
    }

    public function receive(string $taskId): ?array
    {
        return ($this->receive)($taskId);
    }

    public function delete(string $taskId): void
    {
        ($this->delete)($taskId);
    }
}
