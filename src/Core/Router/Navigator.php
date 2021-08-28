<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Router;

use FastRoute\RouteParser\Std;
use Psr\Http\Message\ResponseInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Router\Event\AfterRouteBuildEvent;
use Windwalker\Core\Router\Event\BeforeRouteBuildEvent;
use Windwalker\Core\Router\Exception\RouteNotFoundException;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Event\EventAwareTrait;
use Windwalker\Event\EventEmitter;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Cache\InstanceCacheTrait;

use function Symfony\Component\String\s;

/**
 * The Navigator class.
 */
class Navigator implements NavConstantInterface, EventAwareInterface
{
    use InstanceCacheTrait;
    use EventAwareTrait;

    /**
     * @var RouteBuilder
     */
    protected RouteBuilder $routeBuilder;

    protected int $options = 0;

    public function __construct(
        protected Router $router,
        EventEmitter $dispatcher,
        protected ?AppContext $app = null,
        ?RouteBuilder $routeBuilder = null
    ) {
        $this->routeBuilder = $routeBuilder ?? new RouteBuilder(new Std());
        $this->dispatcher   = $dispatcher;
    }

    public function back(int $options = self::TYPE_PATH): RouteUri
    {
        $options |= $this->options;

        $to = $this->app->getServerRequest()->getServerParams()['HTTP_REFERER']
            ?? $this->app->getSystemUri()->root();

        return new RouteUri($to, null, $this, $options);
    }

    public function self(int $options = self::TYPE_PATH): RouteUri
    {
        $route = $this->getMatchedRoute();
        $to = $route?->getName() ?? $this->app->getSystemUri()->current();

        $vars = [];
        $withoutVars = (bool) ($options & static::WITHOUT_VARS);

        if ($route && !$withoutVars) {
            $keys = [];

            $variants = $this->routeBuilder->parse($route->getPattern());
            $variant = array_pop($variants);

            foreach ($variant as $segment) {
                if (is_array($segment) && isset($segment[0])) {
                    $keys[] = $segment[0];
                }
            }

            $vars = Arr::only($route?->getVars(), $keys);
        }

        return $this->to(
            $to,
            array_merge(
                $vars,
                $this->app->getServerRequest()->getQueryParams()
            ),
            $options
        );
    }

    public function to(string $route, array $query = [], int $options = self::TYPE_PATH): RouteUri
    {
        $options |= $this->options;

        $navigator = $this;

        $event = $this->emit(
            BeforeRouteBuildEvent::class,
            compact('navigator', 'query', 'route', 'options')
        );

        $route   = $event->getRoute();
        $options = $event->getOptions();
        $query   = $event->getQuery();

        $handler = function (array $query) use ($navigator, $options, $route): array {
            $id = $route . ':' . json_encode($query);

            return $this->once(
                'route:' . $id,
                function () use ($query, $route, $options, $navigator) {
                    $routeObject = $this->findRoute($route);

                    if (!$routeObject) {
                        throw new RouteNotFoundException('Route: ' . $route . ' not found.');
                    }

                    [$url, $query] = $this->routeBuilder->build($routeObject->getPattern(), $query);

                    $navigator = $this;
                    $event = $this->emit(
                        AfterRouteBuildEvent::class,
                        compact('navigator', 'query', 'route', 'options', 'url')
                    );

                    $systemUri = $this->app->getSystemUri();
                    $url = $event->getUrl();

                    if ($systemUri->script && $systemUri->script !== 'index.php') {
                        $url = $systemUri->script . '/' . $url;
                    }

                    return [$url, $event->getQuery()];
                }
            );
        };

        return $this->createRouteUri($handler, $query, $options);
    }

    /**
     * createRouteUri
     *
     * @param  \Closure|\Stringable|string  $uri
     * @param  array|null                   $vars
     * @param  int                          $options
     *
     * @return  RouteUri
     */
    public function createRouteUri(mixed $uri, ?array $vars = [], int $options = 0): RouteUri
    {
        return new RouteUri($uri, $vars, $this, $options);
    }

    public function redirectInstant(\Stringable|string $uri, int $code = 303, int $options = 0): ResponseInterface
    {
        return $this->redirect($uri, $code, $options | static::REDIRECT_INSTANT);
    }

    public function redirect(\Stringable|string $uri, int $code = 303, int $options = 0): ResponseInterface
    {
        if ($options & static::REDIRECT_ALLOW_OUTSIDE) {
            $uri = $this->validateRedirectUrl($uri);
        }

        return $this->app->redirect($uri, $code, (bool) ($options & static::REDIRECT_INSTANT));
    }

    public function redirectTo(
        string $route,
        array $query = [],
        int $code = 303,
        int $options = self::TYPE_PATH,
    ): ResponseInterface {
        return $this->app->redirect(
            $this->to($route, $query),
            $code,
            (bool) ($options & static::REDIRECT_INSTANT)
        );
    }

    public function redirectSelf(int $code = 303, int $options = 0): ResponseInterface
    {
        return $this->redirect($this->self(), $code, $options);
    }

    public function validateRedirectUrl(\Stringable|string $uri): string
    {
        $root = $this->app->getSystemUri()->root;

        if (str_starts_with((string) $uri, '/')) {
            $uri = (string) $uri;
        }

        if (stripos($uri, $root) !== 0) {
            $uri = $root;
        }

        return $uri;
    }

    public function absolute(string $url, int $options = RouteUri::TYPE_PATH): string
    {
        $systemUri = $this->app->getSystemUri();

        // if (!$systemUri) {
        //     return Str::ensureLeft($url, '/');
        // }

        return $systemUri->absolute(
            $url,
            (bool) ($options & static::TYPE_FULL)
        );
    }

    /**
     * @return int
     */
    public function getOptions(): int
    {
        return $this->options;
    }

    /**
     * type
     *
     * @param  int  $options
     *
     * @return  $this
     */
    public function withOptions(int $options): static
    {
        $new          = clone $this;
        $new->options = $options;

        return $new;
    }

    /**
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * @return AppContext
     */
    public function getAppContext(): AppContext
    {
        return $this->app;
    }

    /**
     * getMatchedRoute
     *
     * @return  Route|null
     */
    public function getMatchedRoute(): ?Route
    {
        return $this->app->getMatchedRoute();
    }

    /**
     * findRoute
     *
     * @param  string  $route
     *
     * @return  Route|null
     */
    public function findRoute(string $route): ?Route
    {
        if (str_contains($route, '\\')) {
            foreach ($this->router->getRoutes() as $routeObject) {
                $view = $routeObject->getOption('vars')['view'] ?? null;

                if ($view && $view === $route) {
                    return $routeObject;
                }
            }
        }

        $routeObject = $this->router->getRoute($route);

        if (!$routeObject && !str_contains($route, '::')) {
            // Find with namespace
            if ($matched = $this->getMatchedRoute()) {
                $ns = $matched->getExtraValue('namespace');

                if ($ns) {
                    $routeObject = $this->router->getRoute($ns . '::' . $route);
                }
            }
        }

        return $routeObject;
    }
}
