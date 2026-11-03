<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application\Stub;

use Windwalker\Core\Service\LoggerService;

/**
 * LoggerService replacement that records error() calls instead of resolving
 * a real LoggerManager/Monolog channel stack.
 */
class RecordingLoggerService extends LoggerService
{
    /**
     * @var array<int, array{channel: string|array, message: mixed, context: array}>
     */
    public array $errors = [];

    public function __construct()
    {
        // Intentionally skip parent::__construct(): no LoggerManager needed for tests.
    }

    public function error(string|array $channel, mixed $message, array $context = []): static
    {
        $this->errors[] = ['channel' => $channel, 'message' => $message, 'context' => $context];

        return $this;
    }
}
