<?php

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
