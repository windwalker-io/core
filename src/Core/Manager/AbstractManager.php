<?php

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Windwalker\Core\DI\ServiceFactoryInterface;
use Windwalker\Core\DI\ServiceFactoryTrait;

/**
 * The AbstractManager class.
 *
 * @deprecated  Use container tags instead.
 */
abstract class AbstractManager implements ServiceFactoryInterface
{
    use ServiceFactoryTrait;
}
