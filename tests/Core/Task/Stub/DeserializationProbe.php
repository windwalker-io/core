<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Task\Stub;

/**
 * Detects whether object hooks run before payload authentication.
 * Remove when pre-deserialization signature validation is no longer tested.
 */
class DeserializationProbe
{
    public static bool $restored = false;

    public function __wakeup(): void
    {
        self::$restored = true;
    }

    public function __invoke(): mixed
    {
        return 'probe';
    }
}
