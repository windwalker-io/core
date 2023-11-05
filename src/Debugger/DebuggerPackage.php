<?php

declare(strict_types=1);

namespace Windwalker\Debugger;

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageInstaller;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Filesystem\Path;

/**
 * The DebuggerPackage class.
 */
class DebuggerPackage extends AbstractPackage implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->mergeParameters(
            'renderer.paths',
            [
                Path::normalize(__DIR__ . '/../../views/debugger'),
            ],
            Container::MERGE_OVERRIDE
        );
    }

    public function install(PackageInstaller $installer): void
    {
        //
    }

    public static function getName(): string
    {
        return 'debugger';
    }

    protected static function loadComposerJson(): array
    {
        return [];
    }
}
