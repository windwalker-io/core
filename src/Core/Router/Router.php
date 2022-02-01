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
     * @param  Route[]  $routes
     */
    public function __construct(array $routes = [])
    {
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
        $route = Str::ensureLeft(rtrim($route ?? $request->getUri()->getPath(), '/'), '/');
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
                $vars = array_merge(array_map('urldecode', $vars), $route->getVars());
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

        // Match Hosts
        $hosts = $route->getHosts();

        if (($hosts !== []) && !$this->matchHost($request, $hosts, $route)) {
            return false;
        }

        // Match schemes
        $scheme = $route->getScheme();

        return !($scheme && $scheme !== $uri->getScheme());
    }

    /**
     * matchHost
     *
     * @param  ServerRequestInterface  $request
     * @param  array                   $hosts
     * @param  Route                   $route
     *
     * @return bool
     */
    protected function matchHost(ServerRequestInterface $request, array $hosts, Route $route): bool
    {
        $currentHost = $request->getUri()->getHost();
        $found = false;

        foreach ($hosts as $host) {
            $hostRegexes = $this->patternToRegex($host);

            foreach ($hostRegexes as [$hostRegex, $varNames]) {
                preg_match('~' . $hostRegex . '~', $currentHost, $matches);

                if ($matches !== []) {
                    $found = true;
                }

                $variables = array_intersect_key(
                    $matches,
                    array_flip($varNames),
                );

                $route->vars($variables);
            }
        }

        return $found !== false;
    }

    public function getRoute(string $name): ?Route
    {
        $found = $this->routes[$name] ?? null;

        if (!$found) {
            foreach ($this->routes as $route) {
                $aliases = $route->getOption('aliases') ?? [];

                if (in_array($name, $aliases, true)) {
                    return $route;
                }
            }
        }

        return $found;
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

    public function patternToRegex(string $pattern): array
    {
        $items = [];

        foreach ((new Std())->parse($pattern) as $parsedItem) {
            $items[] = $this->buildRegexForRoute($parsedItem);
        }

        return $items;
    }

    /**
     * @see vendor/nikic/fast-route/src/DataGenerator/RegexBasedAbstract.php
     *
     * @param  array  $routeData
     *
     * @return  array
     */
    private function buildRegexForRoute(array $routeData): array
    {
        $regex = '';
        $variables = [];

        foreach ($routeData as $part) {
            if (is_string($part)) {
                $regex .= preg_quote($part, '~');
                continue;
            }

            [$varName, $regexPart] = $part;

            if (isset($variables[$varName])) {
                throw new BadRouteException(
                    sprintf(
                        'Cannot use the same placeholder "%s" twice',
                        $varName
                    )
                );
            }

            if ($this->regexHasCapturingGroups($regexPart)) {
                throw new BadRouteException(
                    sprintf(
                        'Regex "%s" for parameter "%s" contains a capturing group',
                        $regexPart,
                        $varName
                    )
                );
            }

            $variables[$varName] = $varName;
            $regex .= "(?P<$varName>" . $regexPart . ')';
        }

        return [$regex, $variables];
    }

    /**
     * @param  string
     *
     * @return bool
     */
    private function regexHasCapturingGroups(string $regex): bool
    {
        if (!str_contains($regex, '(')) {
            // Needs to have at least a ( to contain a capturing group
            return false;
        }

        // Semi-accurate detection for capturing groups
        return (bool) preg_match(
            '~
                (?:
                    \(\?\(
                  | \[ [^\]\\\\]* (?: \\\\ . [^\]\\\\]* )* \]
                  | \\\\ .
                ) (*SKIP)(*FAIL) |
                \(
                (?!
                    \? (?! <(?![!=]) | P< | \' )
                  | \*
                )
            ~x',
            $regex
        );
    }
}
