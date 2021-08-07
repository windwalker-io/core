<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Renderer;

use Windwalker\DI\Container;
use Windwalker\Utilities\Iterator\PriorityQueue;

/**
 * The LayoutPathResolver class.
 */
class LayoutPathResolver
{
    protected array $aliases = [];

    /**
     * @var array<PathsBag>
     */
    protected array $pathsBags = [];

    /**
     * LayoutPathResolver constructor.
     *
     * @param  Container   $container
     * @param  array|null  $aliases
     */
    public function __construct(protected Container $container, ?array $aliases = null)
    {
        $this->aliases = $aliases ?? $this->container->getParam('renderer.aliases') ?? [];
        $this->namespaces = (array) ($this->container->getParam('renderer.namespaces') ?? []);

        $this->addPaths($this->container->getParam('renderer.paths') ?? []);

        foreach ($this->container->getParam('renderer.namespaces') ?? [] as $ns => $paths) {
            $this->addPaths($paths, null, $ns);
        }
    }

    public function resolveLayout(string $layout, ?PathsBag &$paths = null): string
    {
        while (isset($this->aliases[$layout])) {
            $layouts = (array) $this->aliases[$layout];

            // if ($layout instanceof \Closure) {
            //     $layout = $this->container->call($layout);
            // }
        }

        // Handle Namespaces
        // if (str_contains($layout, '::')) {
        //     $segments = explode('::', $layout, 2) + ['', ''];
        //
        //     if (isset($this->namespaces[$segments[0]])) {
        //         $paths = $this->getPathsBag($segments[0]);
        //
        //         if ($checker) {
        //             foreach ($paths->getClonedPaths() as $path) {
        //                 $lt = $path . $segments[1];
        //
        //                 $exists = $checker($lt, $this);
        //
        //                 if ($exists) {
        //                     $layout = $lt;
        //                 }
        //             }
        //         }
        //
        //         $layout = implode('.', $segments);
        //     }
        // }

        // Handle Aliases
        if (str_starts_with($layout, '@')) {
            $segments = preg_split('[/|\\\\|\.]', $layout, 2);

            while (isset($this->aliases[$segments[0]])) {
                $segments[0] = (array) $this->aliases[$segments[0]];

                // if ($segments[0] instanceof \Closure) {
                //     $segments[0] = $this->container->call($segments[0]);
                // }
            }

            $layout = implode('.', $segments);
        }

        $paths = $this->getPathsBag(static::getNamespaceFromPath($layout));

        return $layout;
    }

    public function alias(string $alias, string $layout): static
    {
        $this->aliases[$layout] = $alias;

        return $this;
    }

    public static function getNamespaceFromPath(string $path): string
    {
        return str_contains($path, '::') ? explode('::', $layout, 2)[0] : 'main';
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * @param  array  $aliases
     *
     * @return  static  Return self to support chaining.
     */
    public function setAliases(array $aliases): static
    {
        $this->aliases = $aliases;

        return $this;
    }

    public function addPaths(string|array $paths, ?int $priority = null, string $ns = 'main'): static
    {
        $priority ??= PriorityQueue::ABOVE_NORMAL;

        $bag = $this->getPathsBag($ns);
        $bag->addPath($paths, $priority);

        return $this;
    }

    public function getPathsBag(string $ns): PathsBag
    {
        return $this->pathsBags[$ns] ??= new PathsBag();
    }

    public function resetPathsBag(string $ns): static
    {
        unset($this->pathsBags[$ns]);

        return $this;
    }

    public function resetPathsBags(): static
    {
        $this->pathsBags = [];

        return $this;
    }
}
