<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Theme;

use Windwalker\Renderer\CompositeRenderer;

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
