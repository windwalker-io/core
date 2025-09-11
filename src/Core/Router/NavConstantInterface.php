<?php

declare(strict_types=1);

namespace Windwalker\Core\Router;

/**
 * Interface NavInterface
 *
 * @deprecated  Use {@see NavOptions} instead.
 */
interface NavConstantInterface
{
    /**
     * @deprecated  Use {@see NavOptions} instead.
     */
    public const TYPE_RAW = 1 << 0;

    /**
     * @deprecated  Use {@see NavOptions} instead.
     */
    public const TYPE_PATH = 1 << 1;

    /**
     * @deprecated  Use {@see NavOptions} instead.
     */
    public const TYPE_FULL = 1 << 2;

    /**
     * @deprecated  Use {@see NavOptions} instead.
     */
    public const DEBUG_ALERT = 1 << 3;

    /**
     * @deprecated  Use {@see NavOptions} instead.
     */
    public const MODE_MUTE = 1 << 4;

    /**
     * @deprecated  Use {@see NavOptions} instead.
     */
    public const MODE_ESCAPE = 1 << 5;

    /**
     * @deprecated  Use {@see NavOptions} instead.
     */
    public const REDIRECT_ALLOW_OUTSIDE = 1 << 6;

    /**
     * @deprecated  Use {@see NavOptions} instead.
     */
    public const REDIRECT_INSTANT = 1 << 7;

    /**
     * @deprecated  Use {@see NavOptions} instead.
     */
    public const WITHOUT_VARS = 1 << 8;

    /**
     * @deprecated  Use {@see NavOptions} instead.
     */
    public const WITHOUT_QUERY = 1 << 9;

    /**
     * @deprecated  Use {@see NavOptions} instead.
     */
    public const IGNORE_EVENTS = 1 << 10;
}
