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
     * registerFile
     *
     * @param  string  $file
     *
     * @return  $this
     */
    public function registerFile(string $file)
    {
        $this->routeDefinitions[] = function (RouteCollector $router) use ($file) {
            include $file;
        };

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
}
