<?php

declare(strict_types=1);

namespace Windwalker\Core\Queue;

use Windwalker\DI\Attributes\Isolation;
use Windwalker\Queue\Queue;

/**
 * The QueueManager class.
 *
 * @method  Queue  get(?string $name = null, ...$args)
 *
 * @deprecated  Use container tags instead.
 */
#[Isolation]
class QueueManager extends QueueFactory
{
    //
}
