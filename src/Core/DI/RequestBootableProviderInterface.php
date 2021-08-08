<?php

/**
 * Part of unicorn project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\DI;

use Windwalker\DI\Container;

/**
 * Interface RequestBootableProviderInterface
 */
interface RequestBootableProviderInterface
{
    public function bootBeforeRequest(Container $container): void;
}
