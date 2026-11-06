<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Package\Stub\MvcFixture\src;

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageInstaller;

/**
 * An {@see AbstractPackage} fixture providing `src/Module/Front/Widget/**`,
 * `src/Entity/Widget.php` and `src/Repository/WidgetRepository.php` so that
 * {@see PackageInstaller::installMVCModules()} can be exercised
 * end-to-end against real files. Keep this fixture if
 * {@see \Windwalker\Tests\Core\Package\PackageInstallerTest} still relies on it.
 */
class MvcFixturePackage extends AbstractPackage
{
    protected static ?string $name = 'acme/mvc-fixture';

    public function install(PackageInstaller $installer): void
    {
        //
    }
}
