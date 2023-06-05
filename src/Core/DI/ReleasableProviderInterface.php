<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2023 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\DI;

use Windwalker\DI\Container;

/**
 * Interface ReleasableProviderInterface
 */
interface ReleasableProviderInterface
{
    public function release(Container $container): void;
}
