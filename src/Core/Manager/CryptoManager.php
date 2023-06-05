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

/**
 * The CryptoManager class.
 *
 * @method CipherInterface get(?string $name = null, ...$args)
 * @method CipherInterface create(?string $name = null, ...$args)
 */
class CryptoManager extends AbstractManager
{
    public function getConfigPrefix(): string
    {
        return 'security.crypto';
    }
}
