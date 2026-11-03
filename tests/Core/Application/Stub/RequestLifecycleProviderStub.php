<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application\Stub;

use Windwalker\Core\DI\RequestBootableProviderInterface;
use Windwalker\Core\DI\RequestReleasableProviderInterface;
use Windwalker\DI\Container;

/**
 * Provider stub that records whether its request-scoped boot/release hooks
 * ran, used to cover WebApplication::bootProvidersBeforeRequest() /
 * releaseProviders() iterating providers that implement these interfaces.
 */
class RequestLifecycleProviderStub implements
    RequestBootableProviderInterface,
    RequestReleasableProviderInterface
{
    public static int $bootCount = 0;

    public static int $releaseCount = 0;

    public static function reset(): void
    {
        static::$bootCount = 0;
        static::$releaseCount = 0;
    }

    public function bootBeforeRequest(Container $container): void
    {
        static::$bootCount++;
    }

    public function releaseAfterRequest(Container $container): void
    {
        static::$releaseCount++;
    }
}
