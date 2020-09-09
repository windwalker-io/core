<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

use Windwalker\DI\ServiceAwareTrait;
use Windwalker\Event\EventAwareTrait;

/**
 * Trait ApplicationTrait
 */
trait ApplicationTrait
{
    use DIPrepareTrait;
    use ServiceAwareTrait {
        resolve as diResolve;
    }
    use EventAwareTrait;
}
