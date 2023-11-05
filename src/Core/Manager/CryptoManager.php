<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Windwalker\Crypt\Symmetric\CipherInterface;
use Windwalker\DI\Attributes\Isolation;

/**
 * The CryptoManager class.
 *
 * @method CipherInterface get(?string $name = null, ...$args)
 * @method CipherInterface create(?string $name = null, ...$args)
 */
#[Isolation]
class CryptoManager extends AbstractManager
{
    public function getConfigPrefix(): string
    {
        return 'security.crypto';
    }
}
