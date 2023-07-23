<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Windwalker\Crypt\Hasher\HasherInterface;

/**
 * The CryptoManager class.
 *
 * @method HasherInterface get(?string $name = null, ...$args)
 * @method HasherInterface create(?string $name = null, ...$args)
 */
class HasherManager extends AbstractManager
{
    public function getConfigPrefix(): string
    {
        return 'security.hasher';
    }
}
