<?php

declare(strict_types=1);

namespace Windwalker\Tests\Testing\Stub;

use Psr\Log\AbstractLogger;

/**
 * Minimal logger that records every log() call for assertions.
 *
 * Shared, general-purpose PSR-3 double; safe to reuse from any test suite
 * that needs to assert on logging behavior.
 */
class SpyLogger extends AbstractLogger
{
    /**
     * @var array<int, array{level: mixed, message: string, context: array}>
     */
    public array $records = [];

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}
