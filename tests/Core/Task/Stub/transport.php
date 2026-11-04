<?php

/**
 * Shared filesystem cache transport for isolated subprocess tests.
 * Remove with the task integration fixtures.
 */

declare(strict_types=1);

use Windwalker\Cache\CachePool;
use Windwalker\Cache\Serializer\PhpSerializer;
use Windwalker\Cache\Storage\FileStorage;
use Windwalker\Core\Task\CacheTaskResultTransport;

return new CacheTaskResultTransport(
    new CachePool(
        new FileStorage(\Windwalker\Utilities\Env::get('TASK_TEST_CACHE')),
        new PhpSerializer()
    )
);
