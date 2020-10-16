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

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Router\Exception\RouteNotFoundException;
use Windwalker\Core\Router\Exception\UnAllowedMethodException;
use Windwalker\Event\EventAwareInterface;

use Windwalker\Event\EventAwareTrait;

use Windwalker\Utilities\TypeCast;

use function FastRoute\simpleDispatcher;

/**
 * The Router class.
 */
class Router implements EventAwareInterface
{
    use EventAwareTrait;

    /**
     * @var Route[]
     */
    protected array $routes = [];

    public function register(string|iterable $paths): static
    {
        $creator = static::createRouteCreator()->load($paths);

        $this->routes = array_merge($this->routes, $creator->compileRoutes());

        return $this;
    }

    public static function createRouteCreator(): RouteCreator
    {
        return new RouteCreator();
    }

    public function getRouteDispatcher(ServerRequestInterface $request, array $options = []): Dispatcher
    {
        return $this->createRouteDispatcher(
            function (RouteCollector $router) use ($request) {
                foreach ($this->routes as $name => $route) {
                    if (!$this->checkRoute($request, $route)) {
                        continue;
                    }

                    // Always use GET since we'll check methods after route matched.
                    // This should speed up the matcher.
                    $router->addRoute(
                        'GET',
                        $route->getPattern(),
                        $route
                    );
                }
            },
            $options
        );
    }

    protected function createRouteDispatcher(callable $define, array $options = []): Dispatcher
    {
        return simpleDispatcher($define, $options);
    }

    public function match(ServerRequestInterface $request): Route
    {
        $path = $request->getUri()->getPath();
        $dispatcher = $this->getRouteDispatcher($request);
        $routeInfo = $dispatcher->dispatch($request->getMethod(), $path);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new RouteNotFoundException('Unable to find this route: ' . $path);
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                throw new UnAllowedMethodException('Method not allowed');
            default:
            case Dispatcher::FOUND:
                [, $route, $vars] = $routeInfo;

                /** @var Route $route */
                $route = clone $route;
                $vars = array_merge($vars, $route->getVars());
                $route->vars($vars);

                return $route;
        }
    }

    public function checkRoute(ServerRequestInterface $request, Route $route): bool
    {
        $uri = $request->getUri();

        // Match methods
        $methods = $route->getMethods();

        if ($methods && !in_array(strtoupper($request->getMethod()), $methods, true)) {
            return false;
        }

        // TODO: Match Host

        // Match schemes
        $scheme = $route->getScheme();

        return !($scheme && $scheme !== $uri->getScheme());
    }
}
