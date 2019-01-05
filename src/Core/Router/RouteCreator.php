<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2019 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Router;

use Windwalker\Core\Package\PackageResolver;
use Windwalker\Data\Data;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Classes\ImmutableHelperTrait;

/**
 * The RouteCreator class.
 *
 * @since  __DEPLOY_VERSION__
 */
class RouteCreator
{
    use RouteConfigureTrait;
    use ImmutableHelperTrait;

    /**
     * Property routes.
     *
     * @var RouteData[]|Data
     */
    protected $routes = [];

    /**
     * Property groups.
     *
     * @var  array
     */
    protected $groups = [];

    /**
     * Property preparedGroups.
     *
     * @var  Data
     */
    protected $preparedGroups = [];

    /**
     * Property group.
     *
     * @var  string
     */
    protected $group;

    /**
     * Property packageResolver.
     *
     * @var  PackageResolver
     */
    protected $packageResolver;

    /**
     * RouteCreator constructor.
     *
     * @param PackageResolver $packageResolver
     * @param string          $group
     */
    public function __construct(PackageResolver $packageResolver, $group = 'root')
    {
        $this->packageResolver = $packageResolver;
        $this->group = $group;

        // Make this object referenced in whole tree
        $this->routes         = new Data();
        $this->preparedGroups = new Data();
    }

    /**
     * group
     *
     * @param string   $group
     * @param array    $data
     * @param callable $callback
     *
     * @return  RouteCreator
     *
     * @since  __DEPLOY_VERSION__
     */
    public function group(string $group, array $data = [], ?callable $callback = null): self
    {
        // Has callback, set group routes.

        // Find parents
        if (isset($data['parents'])) {
            foreach ((array) $data['parents'] as $parent) {
                if (!isset($this->preparedGroups[$parent])) {
                    throw new \LogicException(sprintf(
                        'Unable to find parent group: %s for route group: %s',
                        $parent,
                        $group
                    ));
                }

                $data = Arr::mergeRecursive($this->preparedGroups[$parent], $data);
            }
        }

        // No callback, set prepared group
        $this->preparedGroups[$group] = $data;

        $this->groups[$group] = $data;

        $new = $this->cloneInstance();
        $new->setOptions($data);
        $new->package((string) $this->getOption('package'));
        $new->group = $group;

        if ($callback) {
            $callback($this);

            array_pop($this->groups);
        }

        return $new;
    }

    /**
     * add
     *
     * @param string      $name
     * @param string|null $pattern
     * @param array       $options
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function any(string $name, ?string $pattern = null, array $options = []): RouteData
    {
        $groups = $this->groups;

        $route = new RouteData($name);

        $this->routes[$name] = $route;

        $route->setOptions($options);

        if ($pattern) {
            $route->pattern($pattern);
        }

        // Middlewares
        $middlewares = array_filter(array_column($groups, 'middlewares'), 'is_array');

        if ($middlewares !== []) {
            $middlewares = array_merge(...$middlewares);

            $route->middlewares($middlewares);
        }

        $route->groups($this->groups)
            ->package((string) $this->getOption('package'));

        return $route;
    }

    /**
     * handleRoute
     *
     * @param RouteData $route
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function handleRoute(RouteData $route): RouteData
    {
        $name   = $route->getName();
        $groups = $route->getGroups();

        // Set group data
        $keys = ['methods', 'actions', 'variables', 'requirements', 'scheme', 'port', 'sslPort', 'hooks'];

        foreach ($groups as $groupData) {
            foreach ($keys as $i => $key) {
                if (isset($groupData[$key])) {
                    $route->$key($groupData[$key]);

                    unset($groupData[$key]);
                }
            }

            if (isset($groupData['extra'])) {
                $route->extraValues($groupData['extra']);
            }
        }

        $options = $route->getOptions();

        if (!isset($options['pattern'])) {
            throw new \LogicException('Route: ' . $name . ' has no pattern.');
        }

        // Prefix
        $prefixes = array_filter(array_column($groups, 'prefix'), function ($v) {
            return strlen(trim($v, '/'));
        });

        $options['pattern'] = rtrim(implode('/', $prefixes) . $options['pattern'], '/');

        $options['extra']['controller']  = $options['controller'] ?? '';
        $options['extra']['action']      = $options['actions'] ?? [];
        $options['extra']['package']     = $options['package'] ?? '';
        $options['extra']['hook']        = $options['hooks'] ?? [];
        $options['extra']['middlewares'] = $options['middlewares'] ?? [];
//        $options['extra']['package']     = $options['package'];
        $options['extra']['groups']      = $groups;

        $route->setOptions($options);

        return $route;
    }

    /**
     * registerPackage
     *
     * @param string $package
     * @param string $pattern
     *
     * @return  RouteCreator
     *
     * @since  __DEPLOY_VERSION__
     * @throws \ReflectionException
     */
    public function registerPackage(string $package, string $pattern): self
    {
        return $this->packageResolver->getPackage($package)
            ->registerRoutes($this, $pattern);
    }

    /**
     * get
     *
     * @param string      $name
     * @param string|null $pattern
     * @param array       $options
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function get(string $name, ?string $pattern = null, array $options = []): RouteData
    {
        return $this->any($name, $pattern, $options)->methods('GET');
    }

    /**
     * post
     *
     * @param string      $name
     * @param string|null $pattern
     * @param array       $options
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function post(string $name, ?string $pattern = null, array $options = []): RouteData
    {
        return $this->any($name, $pattern, $options)->methods('POST');
    }

    /**
     * put
     *
     * @param string      $name
     * @param string|null $pattern
     * @param array       $options
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function put(string $name, ?string $pattern = null, array $options = []): RouteData
    {
        return $this->any($name, $pattern, $options)->methods('PUT');
    }

    /**
     * patch
     *
     * @param string      $name
     * @param string|null $pattern
     * @param array       $options
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function patch(string $name, ?string $pattern = null, array $options = []): RouteData
    {
        return $this->any($name, $pattern, $options)->methods('PATCH');
    }

    /**
     * save
     *
     * @param string      $name
     * @param string|null $pattern
     * @param array       $options
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function save(string $name, ?string $pattern = null, array $options = []): RouteData
    {
        return $this->any($name, $pattern, $options)->methods(['PUT', 'PATCH', 'POST']);
    }

    /**
     * delete
     *
     * @param string      $name
     * @param string|null $pattern
     * @param array       $options
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function delete(string $name, ?string $pattern = null, array $options = []): RouteData
    {
        return $this->any($name, $pattern, $options)->methods('DELETE');
    }

    /**
     * prefix
     *
     * @param string $prefix
     *
     * @return  $this
     *
     * @since  __DEPLOY_VERSION__
     */
    public function prefix(string $prefix)
    {
        $this->setOption('prefix', $prefix);

        return $this;
    }

    /**
     * load
     *
     * @param string|array $paths
     *
     * @return  RouteCreator
     *
     * @since  __DEPLOY_VERSION__
     */
    public function load($paths): self
    {
        $paths = (array) $paths;

        $router = $this;

        foreach ($paths as $path) {
            require $path;
        }

        return $this;
    }

    /**
     * register
     *
     * @param callable $callable
     *
     * @return  static
     *
     * @since  __DEPLOY_VERSION__
     */
    public function register(callable $callable)
    {
        $this->groups[$this->group] = $this->options;

        $callable($this);

        array_pop($this->groups);

        return $this;
    }

    /**
     * Method to get property Routes
     *
     * @return  RouteData[]
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getRoutes(): array
    {
        return $this->routes->dump();
    }

    /**
     * Method to get property Groups
     *
     * @return  array
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Method to set property groups
     *
     * @param   array $groups
     *
     * @return  static  Return self to support chaining.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function setGroups($groups)
    {
        $this->groups = $groups;

        return $this;
    }
}
