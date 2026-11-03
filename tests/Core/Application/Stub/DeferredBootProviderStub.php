<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application\Stub;

use Windwalker\DI\BootableDeferredProviderInterface;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * No-op provider exercising ApplicationTrait::registerAllConfigs()'s
 * BootableDeferredProviderInterface branch.
 */
class DeferredBootProviderStub implements ServiceProviderInterface, BootableDeferredProviderInterface
{
    public static int $bootDeferredCalls = 0;

    public function register(Container $container): void
    {
        //
    }

    public function bootDeferred(Container $container): void
    {
        self::$bootDeferredCalls++;
    }
}
