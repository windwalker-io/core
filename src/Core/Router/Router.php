<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Router;

use FastRoute\Dispatcher;

use FastRoute\RouteCollector;

use function FastRoute\simpleDispatcher;

/**
 * The Router class.
 */
class Router
{
    protected array $routeDefinitions = [];

    /**
     * @var Route[]
     */
    protected array $routes = [];

    public function add(Route $route): Route
    {
        return $this->routes[$route->getName()] = $route;
    }

    public function addRoute(string $name, string $pattern, array $options = []): Route
    {
        return $this->add(new Route($name, $pattern, $options));
    }

    /**
     * registerFile
     *
     * @param  string  $file
     *
     * @return  $this
     */
    public function registerFile(string $file)
    {
        $router = $this;
        include $file;

        return $this;
    }

    /**
     * @return array
     */
    public function getRouteDefinitions(): array
    {
        return $this->routeDefinitions;
    }

    /**
     * @param  array  $routeDefinitions
     *
     * @return  static  Return self to support chaining.
     */
    public function setRouteDefinitions(array $routeDefinitions)
    {
        $this->routeDefinitions = $routeDefinitions;

        return $this;
    }

    public function getRouteDispatcher(array $options = []): Dispatcher
    {
        return $this->createRouteDispatcher(
            function (RouteCollector $router) {
                foreach ($this->routeDefinitions as $routeDefinition) {
                    $routeDefinition($router);
                }
            },
            $options
        );
    }

    protected function createRouteDispatcher(callable $define, array $options = []): Dispatcher
    {
        return simpleDispatcher($define, $options);
    }

    public function __call(string $name, array $args)
    {
        $methods = [
            'get',
            'post',
            ''
        ];
    }
}
