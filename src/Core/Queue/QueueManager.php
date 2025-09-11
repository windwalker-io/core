<?php

declare(strict_types=1);

namespace Windwalker\Core\Queue;

use Closure;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Windwalker\Core\Manager\AbstractManager;
use Windwalker\DI\Attributes\Isolation;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Queue\Job\JobController;
use Windwalker\Queue\Queue;
use Windwalker\Queue\QueueMessage;

/**
 * The QueueManager class.
 *
 * @method Queue get(?string $name = null, ...$args)
 *
 * @deprecated  Use container tags instead.
 */
#[Isolation]
class QueueManager extends QueueFactory
{
    //
}
