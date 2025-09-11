<?php

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Psr\Log\LoggerInterface;
use Windwalker\Core\Factory\LoggerFactory;
use Windwalker\DI\Attributes\Isolation;

/**
 * The LoggerManager class.
 *
 * @method LoggerInterface create(?string $name = null, ...$args)
 * @method LoggerInterface get(?string $name = null, ...$args)
 *
 * @deprecated  Use container tags instead.
 */
#[Isolation]
class LoggerManager extends LoggerFactory
{
   //
}
