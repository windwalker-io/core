<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Queue\Stub;

/**
 * Minimal invokable, serializable job used where `QueueMessage::jsonSerialize()`
 * (which serializes the job) must be exercised, unlike a plain closure job
 * which cannot be serialized.
 */
class StubInvokableJob
{
    public function __invoke(): void {}
}
