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

/**
 * The LayoutPathResolver class.
 */
class LayoutPathResolver
{
    protected array $aliases = [];

    /**
     * LayoutPathResolver constructor.
     *
     * @param  Container   $container
     * @param  array|null  $aliases
     */
    public function __construct(protected Container $container, ?array $aliases = null)
    {
        $this->aliases = $aliases ?? $this->container->getParam('renderer.aliases') ?? [];
    }

    public function resolveLayout(string $layout): string
    {
        while (isset($this->aliases[$layout])) {
            $layout = $this->aliases[$layout];

            // if ($layout instanceof \Closure) {
            //     $layout = $this->container->call($layout);
            // }
        }

        if (str_starts_with($layout, '@')) {
            $segments = preg_split('[/|\\\\|\.]', $layout, 2);

            while (isset($this->aliases[$segments[0]])) {
                $segments[0] = $this->aliases[$segments[0]];

                // if ($segments[0] instanceof \Closure) {
                //     $segments[0] = $this->container->call($segments[0]);
                // }
            }

            $layout = implode('.', $segments);
        }


        return $layout;
    }

    public function alias(string $alias, string $layout): static
    {
        $this->aliases[$layout] = $alias;

        return $this;
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
}
