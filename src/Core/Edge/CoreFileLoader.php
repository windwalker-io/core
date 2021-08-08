<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Edge;

use Windwalker\Core\Manager\Logger;
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
     */
    public function __construct(
        protected EdgeFileLoader $loader,
        protected LayoutPathResolver $pathResolver,
        protected array $extensions = []
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
        return $this->pathResolver->resolveLayout(
            $key,
            $this->extensions
        );
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

    /**
     * @return array
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * @param  array  $extensions
     *
     * @return  static  Return self to support chaining.
     */
    public function setExtensions(array $extensions): static
    {
        $this->extensions = $extensions;

        return $this;
    }
}
