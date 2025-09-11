<?php

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Psr\Log\LoggerInterface;
use Windwalker\Cache\CachePool;
use Windwalker\Core\Factory\CacheFactory;
use Windwalker\DI\Attributes\Factory;
use Windwalker\DI\Attributes\Isolation;
use Windwalker\DI\Container;
use Windwalker\DI\Definition\ObjectBuilderDefinition;

/**
 * The CacheManager class.
 *
 * @method CachePool get(?string $name = null, ...$args)
 * @method CachePool create(?string $name = null, ...$args)
 *
 * @deprecated  Use container tags instead.
 */
#[Isolation]
class CacheManager extends CacheFactory
{
    //
}
