<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Package\Stub\WithComposerJson\Src;

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageInstaller;

/**
 * An {@see AbstractPackage} fixture that sits next to a real `composer.json` fixture
 * file (one directory up from this file), used to verify `AbstractPackage::composerJson()`
 * reads and caches it, `getName()` falls back to the `name` field when `$name` is not set,
 * and `dir()` / `root()` / `path()` resolve relative to this file's real location. Keep
 * this fixture if {@see \Windwalker\Tests\Core\Package\AbstractPackageTest} still relies on it.
 */
class WithComposerJsonPackage extends AbstractPackage
{
    public function install(PackageInstaller $installer): void
    {
        //
    }
}
