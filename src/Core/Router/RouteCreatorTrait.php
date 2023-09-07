<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Router;

use Windwalker\Utilities\Arr;
use Windwalker\Utilities\TypeCast;

use function Windwalker\glob_all;

/**
 * Trait RouteCreatorTrait
 */
trait RouteCreatorTrait
{
    /**
     * @param  string                $name
     * @param  string|callable|null  $pattern
     * @param  array                 $options
     *
     * @return  Route
     *
     * @since  3.5
     */
    public function any(string $name, string|callable|null $pattern = null, array $options = []): Route
    {
        $groups = $this->groups;
        $handler = null;

        if ($pattern === null || is_callable($pattern)) {
            $handler = $pattern;
            $pattern = $name;
            $name = md5($pattern);
        }

        $route = new Route($name, $pattern);

        $groupNames = array_keys($groups);
        $groupNames[] = $name;

        $this->routes[implode('.', $groupNames)] = $route;

        $route->setOptions($options);

        if ($pattern) {
            $pattern = '/' . ltrim($pattern, '/');

            $route->pattern($pattern);
        }

        // Middlewares
        $middlewares = array_filter(array_column($groups, 'middlewares'), 'is_array');

        if ($middlewares !== []) {
            $middlewares = array_merge(...$middlewares);

            $route->middlewares($middlewares);
        }

        // Subscribers
        $subscribers = array_filter(
            array_column($groups, 'subscribers'),
            'is_array'
        );

        if ($subscribers !== []) {
            foreach ($subscribers as $subs) {
                $route->subscribes($subs);
            }
        }

        $route->groups($this->groups);

        if ($handler) {
            $route->handler($handler);
        }

        return $route;
    }

    /**
     * @param  string    $group
     * @param  array     $data
     * @param ?callable  $callback
     *
     * @return  static
     *
     * @since  3.5
     */
    public function group(string $group, array $data = [], ?callable $callback = null): static
    {
        // No callback, set prepared group
        $this->preparedGroups[$group] = $data;

        $new = clone $this;
        $new->setOptions($data);
        $new->group = $group;
        $new->groups[$group] = $data;

        // Find parents
        if (isset($data['parents'])) {
            $this->parents((array) $data['parents']);
        }

        if ($callback) {
            $this->groups[$group] = $data;

            $callback($this);

            array_pop($this->groups);
        }

        return $new;
    }

    /**
     * @param  array  $parents
     *
     * @return  static
     *
     * @since  3.5
     */
    public function parents(array $parents): self
    {
        if ($parents === []) {
            return $this;
        }

        $data = [];

        foreach ($parents as $parent) {
            if (!isset($this->preparedGroups[$parent])) {
                throw new LogicException(
                    sprintf(
                        'Unable to find parent group: %s for route group: %s',
                        $parent,
                        $this->group
                    )
                );
            }

            $data[] = $this->preparedGroups[$parent];
        }

        $data = Arr::mergeRecursive(...$data);

        $this->options = array_merge($data, $this->options);

        return $this;
    }

    /**
     * prefix
     *
     * @param  string  $prefix
     *
     * @return  $this
     *
     * @since  3.5
     */
    public function prefix(string $prefix): static
    {
        $this->setOption('prefix', $prefix);

        return $this;
    }

    /**
     * namespace
     *
     * @param  string  $namespace
     *
     * @return  $this
     */
    public function namespace(string $namespace): static
    {
        $this->setOption('namespace', $namespace);

        return $this;
    }

    /**
     * @param  string|iterable|callable  $paths
     *
     * @return  RouteCreator
     *
     * @since  3.5
     */
    public function load(string|iterable|callable $paths): static
    {
        if (is_callable($paths)) {
            $paths($this);

            return $this;
        }

        $paths = TypeCast::toArray($paths);

        $files = glob_all($paths);
        $router = $this;

        foreach ($files as $file) {
            require $file;
        }

        return $this;
    }

    /**
     * @param  string|array  $paths
     *
     * @return  RouteCreator
     *
     * @since  3.5
     */
    public function loadFolder(string|array $paths): static
    {
        $paths = TypeCast::toArray($paths);

        $router = $this;

        foreach ($paths as $path) {
            $files = \Windwalker\glob($path . '/*.php');

            $this->load($files);
        }

        return $this;
    }

    /**
     * @param  callable  $callable
     *
     * @return  static
     *
     * @since  3.5
     */
    public function register(callable $callable): static
    {
        $this->groups[$this->group] = $this->options;

        $callable($this);

        array_pop($this->groups);

        return $this;
    }

    /**
     * Method to get property Routes
     *
     * @return  Route[]
     *
     * @since  3.5
     */
    public function getRoutes(): array
    {
        return $this->routes->dump();
    }

    public function compileRoutes(): array
    {
        $routes = [];

        foreach ($this->getRoutes() as $route) {
            $route = $route->compile();
            $routes[$route->getName()] = $route;
        }

        return $routes;
    }

    /**
     * Method to get property Groups
     *
     * @return  array
     *
     * @since  3.5
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Method to set property groups
     *
     * @param  array  $groups
     *
     * @return  static  Return self to support chaining.
     *
     * @since  3.5
     */
    public function setGroups(array $groups): static
    {
        $this->groups = $groups;

        return $this;
    }
}
