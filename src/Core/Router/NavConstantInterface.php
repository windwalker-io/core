<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Router;

/**
 * Interface NavInterface
 */
interface NavConstantInterface
{
    public const TYPE_RAW = 1 << 0;

    public const TYPE_PATH = 1 << 1;

    public const TYPE_FULL = 1 << 2;

    public const DEBUG_ALERT = 1 << 3;

    public const MODE_MUTE = 1 << 4;

    public const MODE_ESCAPE = 1 << 5;

    public const REDIRECT_ALLOW_OUTSIDE = 1 << 6;

    public const REDIRECT_INSTANT = 1 << 7;
}
