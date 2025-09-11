<?php

declare(strict_types=1);

namespace Windwalker\Core\Queue;

use Windwalker\Core\Manager\AbstractManager;
use Windwalker\DI\Attributes\Isolation;
use Windwalker\Queue\Failer\QueueFailerInterface;

/**
 * The QueueFailerManager class.
 *
 * @method QueueFailerInterface get(?string $name = null, ...$args)
 *
 * @deprecated  Use container tags instead.
 */
#[Isolation]
class QueueFailerManager extends QueueFailerFactory
{
}
