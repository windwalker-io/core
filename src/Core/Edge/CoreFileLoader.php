<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Edge;

use RuntimeException;
use Windwalker\Core\Renderer\LayoutPathResolver;
use Windwalker\Edge\Exception\LayoutNotFoundException;
use Windwalker\Edge\Loader\EdgeFileLoader;
use Windwalker\Edge\Loader\EdgeLoaderInterface;
use Windwalker\Utilities\Cache\InstanceCacheTrait;

/**
 * The EdgeFileLoader class.
 */
class CoreFileLoader implements EdgeLoaderInterface
{
    use InstanceCacheTrait;

    /**
     * EdgeFileLoader constructor.
     *
     * @param  EdgeFileLoader      $loader
     * @param  LayoutPathResolver  $pathResolver
     * @param  array               $extensions
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
        return $this->once(
            $key,
            function () use ($key) {
                try {
                    return $this->pathResolver->resolveLayout(
                        $key,
                        $this->extensions
                    );
                } catch (RuntimeException $e) {
                    if ($this->loader->has($key)) {
                        return $this->loader->find($key);
                    }

                    throw new LayoutNotFoundException(
                        $e->getMessage(),
                        $e->getCode(),
                        $e
                    );
                }
            }
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
