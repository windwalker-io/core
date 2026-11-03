<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Package\Stub;

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageInstaller;

/**
 * An {@see AbstractPackage} fixture that overrides the protected static `$name` property,
 * used to verify `AbstractPackage::getName()` returns it directly without consulting
 * `composerJson()`. Keep this fixture if {@see \Windwalker\Tests\Core\Package\AbstractPackageTest}
 * still relies on it.
 */
class NamedPackage extends AbstractPackage
{
    protected static ?string $name = 'acme/explicit-name';

    public function install(PackageInstaller $installer): void
    {
        //
    }
}
