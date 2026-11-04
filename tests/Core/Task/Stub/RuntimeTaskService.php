<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Task\Stub;

use LogicException;

/**
 * Non-serializable child service for captured-data/runtime-injection tests.
 * Remove when those task integration tests are removed.
 */
class RuntimeTaskService
{
    public function __serialize(): array
    {
        throw new LogicException('Runtime services must not be serialized.');
    }

    public function process(object $entity, array $params, array $ids): array
    {
        return ['entity' => $entity->value, 'params' => $params, 'ids' => $ids, 'pid' => getmypid()];
    }
}
