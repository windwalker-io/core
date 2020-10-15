<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Router;

use Windwalker\Data\Collection;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\TypeCast;

/**
 * The RouteCreator class.
 */
class RouteCreator
{
    use RouteConfigurationTrait;

    /**
     * @var Route[]|Collection
     */
    protected ?Collection $routes = null;

    /**
     * Property groups.
     *
     * @var  array
     */
    protected array $groups = [];

    /**
     * Property preparedGroups.
     *
     * @var  ?Collection
     */
    protected ?Collection $preparedGroups = null;

    /**
     * Property group.
     *
     * @var  string
     */
    protected string $group;

    /**
     * RouteCreator constructor.
     *
     * @param  string  $group
     */
    public function __construct(string $group = 'root')
    {
        $this->group = $group;

        $this->routes = new Collection();
        $this->preparedGroups = new Collection();
    }

    /**
     * group
     *
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
        $new->group          = $group;
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
     * parents
     *
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
                throw new \LogicException(
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
     * add
     *
     * @param  string       $name
     * @param  string|null  $pattern
     * @param  array        $options
     *
     * @return  Route
     *
     * @since  3.5
     */
    public function any(string $name, string $pattern = null, array $options = []): Route
    {
        $groups = $this->groups;

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

        $route->groups($this->groups);

        return $route;
    }

    /**
     * get
     *
     * @param  string       $name
     * @param  string|null  $pattern
     * @param  array        $options
     *
     * @return  Route
     *
     * @since  3.5
     */
    public function get(string $name, ?string $pattern = null, array $options = []): Route
    {
        return $this->any($name, $pattern, $options)->methods('GET');
    }

    /**
     * post
     *
     * @param  string       $name
     * @param  string|null  $pattern
     * @param  array        $options
     *
     * @return  Route
     *
     * @since  3.5
     */
    public function post(string $name, ?string $pattern = null, array $options = []): Route
    {
        return $this->any($name, $pattern, $options)->methods('POST');
    }

    /**
     * put
     *
     * @param  string       $name
     * @param  string|null  $pattern
     * @param  array        $options
     *
     * @return  Route
     *
     * @since  3.5
     */
    public function put(string $name, ?string $pattern = null, array $options = []): Route
    {
        return $this->any($name, $pattern, $options)->methods('PUT');
    }

    /**
     * patch
     *
     * @param  string       $name
     * @param  string|null  $pattern
     * @param  array        $options
     *
     * @return  Route
     *
     * @since  3.5
     */
    public function patch(string $name, ?string $pattern = null, array $options = []): Route
    {
        return $this->any($name, $pattern, $options)->methods('PATCH');
    }

    /**
     * save
     *
     * @param  string       $name
     * @param  string|null  $pattern
     * @param  array        $options
     *
     * @return  Route
     *
     * @since  3.5
     */
    public function save(string $name, ?string $pattern = null, array $options = []): Route
    {
        return $this->any($name, $pattern, $options)->methods(['PUT', 'PATCH', 'POST']);
    }

    /**
     * delete
     *
     * @param  string       $name
     * @param  string|null  $pattern
     * @param  array        $options
     *
     * @return  Route
     *
     * @since  3.5
     */
    public function delete(string $name, ?string $pattern = null, array $options = []): Route
    {
        return $this->any($name, $pattern, $options)->methods('DELETE');
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
     * load
     *
     * @param  string|array  $paths
     *
     * @return  RouteCreator
     *
     * @since  3.5
     */
    public function load(string|array $paths): static
    {
        $paths = TypeCast::toArray($paths);

        $router = $this;

        foreach ($paths as $path) {
            $files = \Windwalker\glob($path);

            foreach ($files as $file) {
                require $file;
            }
        }

        return $this;
    }

    /**
     * loadFolder
     *
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
     * register
     *
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
            $routes[$route->getName()] = $route->compile();
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
