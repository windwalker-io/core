<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Edge;

use Windwalker\Core\Renderer\LayoutPathResolver;
use Windwalker\Core\Renderer\PathsBag;
use Windwalker\Core\Theme\ThemeInterface;
use Windwalker\Edge\Exception\LayoutNotFoundException;
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
     * @param  LayoutPathResolver  $pathResolver
     * @param  ThemeInterface  $theme
     */
    public function __construct(
        protected EdgeFileLoader $loader,
        protected LayoutPathResolver $pathResolver,
        protected ThemeInterface $theme
    ) {
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
        // if (str_starts_with($key, '@theme')) {
        //     $key = ltrim(Str::removeLeft($key, '@theme'), './');
        //
        //     $key = $this->theme->path($key);
        // }
show($key);exit(' @Checkpoint');
        /** @var PathsBag $paths */
        $key = $this->pathResolver->resolveLayout($key, $paths);

        foreach (iterator_to_array($paths->getClonedPaths()) as $path) {
            $this->loader->addPath($path);
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
        // $path = $this->pathResolver->resolveLayout($path);

        return $this->loader->load($path);
    }

    /**
     * has
     *
     * @param  string  $key
     *
     * @return  bool
     */
    public function has(string $key): bool
    {
        try {
            return $this->find($key) !== null;
        } catch (LayoutNotFoundException) {
            return false;
        }
    }
}
