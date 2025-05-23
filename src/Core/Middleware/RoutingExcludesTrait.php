<?php

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Router\RouteUri;
use Windwalker\DI\Attributes\Inject;
use Windwalker\Http\Response\RedirectResponse;
use Windwalker\Uri\UriNormalizer;

trait RoutingExcludesTrait
{
    #[Inject]
    protected AppContext $app;

    abstract public function getExcludes(): mixed;

    protected function isExclude(): bool|RouteUri|ResponseInterface
    {
        $excludes = $this->getExcludes();

        if (is_callable($excludes)) {
            [$route, $uri] = $this->getRouteAndUri();

            $result = $this->app->call($excludes, compact('route', 'uri'));

            if (is_bool($result)) {
                return $result;
            }

            if ($result instanceof RedirectResponse) {
                return $result;
            }

            return new RedirectResponse($result);
        }

        if ($excludes) {
            $excludes = (array) $excludes;

            [$route, $uri] = $this->getRouteAndUri();

            foreach ($excludes as $exclude) {
                if (str_starts_with($exclude, '/')) {
                    if ($uri === $exclude) {
                        return true;
                    }

                    if (str_contains($exclude, '*') && fnmatch($exclude, $uri)) {
                        return true;
                    }
                } else {
                    // Route
                    if ($route === $exclude) {
                        return true;
                    }

                    if (str_contains($exclude, '*') && fnmatch($exclude, $route)) {
                        return true;
                    }
                }
            }

            return in_array($route, $excludes, true);
        }

        return false;
    }

    /**
     * @return  array<string>
     */
    private function getRouteAndUri(): array
    {
        $route = $this->app->getMatchedRoute()?->getName();
        $uri = $this->app->getSystemUri()->route();
        $uri = UriNormalizer::normalizePath($uri);
        $uri = UriNormalizer::ensureRoot($uri);

        return [$route, $uri];
    }
}
