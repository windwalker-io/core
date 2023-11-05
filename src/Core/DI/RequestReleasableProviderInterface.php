<?php

declare(strict_types=1);

namespace Windwalker\Core\DI;

use Windwalker\DI\Container;

/**
 * Interface RequestReleasableProviderInterface
 */
interface RequestReleasableProviderInterface
{
    public function releaseAfterRequest(Container $container): void;
}
