<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Edge;

use Windwalker\Core\Theme\ThemeInterface;
use Windwalker\DI\Attributes\Inject;
use Windwalker\Edge\Loader\EdgeFileLoader;
use Windwalker\Edge\Loader\EdgeLoaderInterface;
use Windwalker\Utilities\Str;

/**
 * The EdgeFileLoader class.
 */
class CoreFileLoader implements EdgeLoaderInterface
{
    /**
     * EdgeFileLoader constructor.
     *
     * @param  EdgeFileLoader  $loader
     * @param  ThemeInterface  $theme
     */
    public function __construct(protected EdgeFileLoader $loader, protected ThemeInterface $theme)
    {
    }

    /**
     * find
     *
     * @param  string  $key
     *
     * @return  string
     */
    public function find(string $key): string
    {
        if (str_starts_with($key, '@theme')) {
            $key = ltrim(Str::removeLeft($key, '@theme'), './');

            $key = $this->theme->path($key);
        }

        return $this->loader->find($key);
    }

    /**
     * loadFile
     *
     * @param  string  $path
     *
     * @return  string
     */
    public function load(string $path): string
    {
        return $this->loader->load($path);
    }
}
