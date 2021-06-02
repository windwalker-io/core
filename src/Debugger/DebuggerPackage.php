<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Debugger;

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageInstaller;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Filesystem\Path;
use Windwalker\Renderer\CompositeRenderer;

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
                Path::normalize(__DIR__ . '/../../views/debugger')
            ]
        );
        $container->mergeParameters(
            'asset.import_map.imports',
            [
                '@core/' => 'vendor/@windwalker-io/core/dist/'
            ]
        );
    }

    public function install(PackageInstaller $installer): void
    {
        //
    }
}
