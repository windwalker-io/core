<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Task\Stub;

use Windwalker\Core\Application\ApplicationInterface;

/**
 * Invokable task requiring a child-container service for command injection tests.
 * Remove when task service-injection tests are removed.
 */
class ServiceTask
{
    public function __invoke(ApplicationInterface $app): array
    {
        return ['injected' => $app->isCliRuntime()];
    }
}
