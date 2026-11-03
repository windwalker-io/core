<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Package\Stub;

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageInstaller;

/**
 * A minimal {@see AbstractPackage} fixture with no `composer.json` next to it and no
 * explicit `$name` override, used to verify `AbstractPackage::getName()`'s fallback to a
 * kebab-cased class name, and `dir()` / `root()` / `path()` resolution from the real
 * file location. Keep this fixture if {@see \Windwalker\Tests\Core\Package\AbstractPackageTest}
 * still relies on it.
 */
class FallbackNamePackage extends AbstractPackage
{
    public function install(PackageInstaller $installer): void
    {
        //
    }
}
