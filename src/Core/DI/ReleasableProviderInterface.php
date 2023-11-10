<?php

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
