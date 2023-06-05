<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Theme;

/**
 * The AbstractTheme class.
 */
abstract class AbstractTheme implements ThemeInterface
{
    public function path(string $path): string
    {
        return $this->getViewPrefix() . '/' . $path;
    }
}
