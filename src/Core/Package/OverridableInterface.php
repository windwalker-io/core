<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Package;

/**
 * Interface OverridableInterface
 */
interface OverridableInterface
{
    public function override(PackageInstaller $installer): void;
}
