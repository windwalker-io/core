<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Language\Stub;

use Windwalker\Core\Package\AbstractPackage;
use Windwalker\Core\Package\PackageInstaller;

/**
 * Minimal package used only by LangServiceTest to exercise
 * LangService::loadAllFromVendor()'s class-string branch.
 *
 * Remove this stub if that branch is ever covered differently or removed.
 */
class DemoLanguagePackage extends AbstractPackage
{
    protected static ?string $name = 'demo-package';

    public function install(PackageInstaller $installer): void
    {
        // Not used in tests.
    }
}
