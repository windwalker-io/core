<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Theme;

/**
 * Interface ThemeInterface
 */
interface ThemeInterface
{
    public function getViewPrefix(): string;

    public function path(string $path): string;
}
