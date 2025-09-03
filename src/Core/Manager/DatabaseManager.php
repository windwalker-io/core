<?php

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Windwalker\Core\Factory\DatabaseServiceFactory;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\DI\Attributes\Isolation;

/**
 * The DatabaseManager class.
 *
 * @method DatabaseAdapter get(?string $name = null, ...$args)
 *
 * @deprecated  Use container tags instead.
 */
#[Isolation]
class DatabaseManager extends DatabaseServiceFactory
{
    //
}
