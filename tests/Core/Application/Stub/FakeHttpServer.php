<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application\Stub;

use Windwalker\Http\Server\ServerInterface;

/**
 * Minimal no-op ServerInterface implementation for WebApplication::bootForServer() tests.
 */
class FakeHttpServer implements ServerInterface
{
    public function listen(string $host = '0.0.0.0', int $port = 0, array $options = []): void
    {
        //
    }

    public function stop(): void
    {
        //
    }

    public function subscribe(object $subscriber, ?int $priority = null): static
    {
        return $this;
    }

    public function on(string $event, callable $callable, ?int $priority = null): static
    {
        return $this;
    }
}
