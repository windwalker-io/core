<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Controller\Stub;

use Windwalker\Core\Controller\ControllerInterface;

/**
 * Minimal `ControllerInterface` implementation used to exercise
 * `ControllerDispatcher::prepareArrayCallable()`'s "handler is a
 * ControllerInterface" branch.
 */
class ImplementsControllerInterfaceStub implements ControllerInterface
{
    public function execute(string $task, array $args = []): mixed
    {
        return $task . ':' . ($args['id'] ?? 'none');
    }
}
