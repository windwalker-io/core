<?php

declare(strict_types=1);

namespace Windwalker\Core\Testing\Stub;

use Windwalker\Http\Server\ServerInterface;

/**
 * Minimal no-op ServerInterface implementation.
 *
 * Shared, general-purpose double; safe to reuse from any test suite that
 * needs a fake HTTP server without a real listen loop.
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
