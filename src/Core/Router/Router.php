<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Router;

use FastRoute\BadRouteException;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Windwalker\Core\Router\Exception\RouteNotFoundException;
use Windwalker\Core\Router\Exception\UnAllowedMethodException;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Event\EventAwareTrait;

use Windwalker\Utilities\Str;

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

    /**
     * Router constructor.
     *
     * @param  Route[]            $routes
     */
    public function __construct(array $routes = []) {
        $this->routes = $routes;
    }

    public function register(string|iterable|callable $paths): static
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

                    try {
                        // Always use GET since we'll check methods after route matched.
                        // This should speed up the matcher.
                        $router->addRoute(
                            'GET',
                            $route->getPattern(),
                            $route
                        );
                    } catch (BadRouteException $e) {
                        throw new BadRouteException(
                            $e->getMessage() . ' - ' . $route->getPattern(),
                            $e->getCode(),
                            $e
                        );
                    }
                }
            },
            $options
        );
    }

    protected function createRouteDispatcher(callable $define, array $options = []): Dispatcher
    {
        return simpleDispatcher($define, $options);
    }

    public function match(ServerRequestInterface $request, ?string $route = null): Route
    {
        $route      = Str::ensureLeft(rtrim($route ?? $request->getUri()->getPath(), '/'), '/');
        $dispatcher = $this->getRouteDispatcher($request);

        // Always use GET to match route since FastRoute dose not supports match all methods.
        // The method check has did before this method.
        $routeInfo = $dispatcher->dispatch('GET', $route);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new RouteNotFoundException('Unable to find this route: ' . $route);
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                throw new UnAllowedMethodException('Method not allowed');
            default:
            case Dispatcher::FOUND:
                [, $route, $vars] = $routeInfo;

                /** @var Route $route */
                $route = clone $route;
                $vars  = array_merge(array_map('urldecode', $vars), $route->getVars());
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

    public function getRoute(string $name): ?Route
    {
        return $this->routes[$name] ?? null;
    }

    /**
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @param  Route[]  $routes
     *
     * @return  static  Return self to support chaining.
     */
    public function setRoutes(array $routes): static
    {
        $this->routes = $routes;

        return $this;
    }
}
