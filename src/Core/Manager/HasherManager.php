<?php

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Windwalker\Crypt\Hasher\HasherInterface;
use Windwalker\DI\Attributes\Isolation;

/**
 * The CryptoManager class.
 *
 * @method HasherInterface get(?string $name = null, ...$args)
 * @method HasherInterface create(?string $name = null, ...$args)
 */
#[Isolation]
class HasherManager extends AbstractManager
{
    public function getConfigPrefix(): string
    {
        return 'security.hasher';
    }
}
