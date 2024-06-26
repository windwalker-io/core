<?php

declare(strict_types=1);

namespace Windwalker\Core\Router;

use Closure;
use FastRoute\RouteParser\Std;
use Psr\Http\Message\ResponseInterface;
use Stringable;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Event\CoreEventAwareTrait;
use Windwalker\Core\Router\Event\AfterRouteBuildEvent;
use Windwalker\Core\Router\Event\BeforeRouteBuildEvent;
use Windwalker\Core\Router\Exception\RouteNotFoundException;
use Windwalker\Data\Collection;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Event\EventAwareTrait;
use Windwalker\Event\EventEmitter;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Cache\InstanceCacheTrait;
use Windwalker\Utilities\TypeCast;

/**
 * The Navigator class.
 */
class Navigator implements NavConstantInterface, EventAwareInterface
{
    use InstanceCacheTrait;
    use CoreEventAwareTrait;

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
        $this->dispatcher = $dispatcher;
    }

    public function back(int $options = self::TYPE_PATH): RouteUri
    {
        $options |= $this->options;

        $to = $this->localReferrer() ?? $this->getSystemUri()->root();

        return new RouteUri($to, null, $this, $options);
    }

    public function referrer(): ?string
    {
        return $this->app->getServerRequest()->getServerParams()['HTTP_REFERER'] ?? null;
    }

    public function localReferrer(): ?string
    {
        $referrer = $this->referrer();

        if ($referrer === null) {
            return null;
        }

        return $this->isLocalUrl((string) $referrer) ? $referrer : null;
    }

    public function self(int $options = self::TYPE_PATH): RouteUri
    {
        $route = $this->getMatchedRoute();
        $to = $route?->getName() ?? $this->getSystemUri()->current();

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

        $query = ($options & static::WITHOUT_QUERY) ? [] : $this->app->getServerRequest()->getQueryParams();

        return $this->to(
            $to,
            array_merge(
                $vars,
                $query
            ),
            $options
        );
    }

    public function selfNoQuery(int $options = self::TYPE_PATH): RouteUri
    {
        return $this->self($options | static::WITHOUT_QUERY);
    }

    public function to(string $route, array $query = [], int $options = self::TYPE_PATH): RouteUri
    {
        $options |= $this->options;

        $id = $route . ':' . $options;

        if ($query !== []) {
            $id .= ':' . json_encode($query);
        }

        return $this->once(
            $id,
            function () use ($options, $query, $route) {
                $navigator = $this;

                if (!($options & static::IGNORE_EVENTS)) {
                    $event = $this->emit(
                        BeforeRouteBuildEvent::class,
                        compact('navigator', 'query', 'route', 'options')
                    );

                    $route = $event->getRoute();
                    $options = $event->getOptions();
                    $query = $event->getQuery();
                }

                $handler = function (array $query) use ($navigator, $options, $route): array {
                    $routeObject = $this->findRoute($route);

                    if (!$routeObject) {
                        throw new RouteNotFoundException('Route: ' . $route . ' not found.');
                    }

                    [$url, $query] = $this->routeBuilder->build($routeObject->getPattern(), $query);

                    $navigator = $this;

                    if (!($options & static::IGNORE_EVENTS)) {
                        $event = $this->emit(
                            AfterRouteBuildEvent::class,
                            compact('navigator', 'query', 'route', 'options', 'url')
                        );

                        $query = $event->getQuery();
                        $url = $event->getUrl();
                    }

                    $systemUri = $this->getSystemUri();

                    if (!$systemUri::isAbsoluteUrl($url) && $systemUri->script && $systemUri->script !== 'index.php') {
                        $url = $systemUri->script . '/' . $url;
                    }

                    return [$url, $query, $routeObject];
                };

                return $this->createRouteUri($handler, $query, $options);
            }
        );
    }

    /**
     * createRouteUri
     *
     * @param  Closure|Stringable|string  $uri
     * @param  array|null                  $vars
     * @param  int                         $options
     *
     * @return  RouteUri
     */
    public function createRouteUri(mixed $uri, ?array $vars = [], int $options = 0): RouteUri
    {
        return new RouteUri($uri, $vars, $this, $options);
    }

    public function redirectInstant(Stringable|string $uri, int $code = 303, int $options = 0): ResponseInterface
    {
        return $this->redirect($uri, $code, $options | static::REDIRECT_INSTANT);
    }

    public function redirect(Stringable|string $uri, int $code = 303, int $options = 0): ResponseInterface
    {
        if (!($options & static::REDIRECT_ALLOW_OUTSIDE)) {
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

    public function isLocalUrl(Stringable|string $uri): bool
    {
        $root = $this->getSystemUri()->root;

        if (str_starts_with((string) $uri, '/')) {
            return true;
        }

        return stripos((string) $uri, $root) === 0;
    }

    public function validateRedirectUrl(Stringable|string $uri): string
    {
        $root = $this->getSystemUri()->root;

        $uri = (string) $uri;

        return $this->isLocalUrl($uri) ? $uri : $root;
    }

    public function absolute(string $url, int $options = RouteUri::TYPE_PATH): string
    {
        $systemUri = $this->getSystemUri();

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
        $new = clone $this;
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

    public function getRouteName(): ?string
    {
        return $this->getMatchedRoute()?->getName();
    }

    public function isActive(string|array $path, Closure|array|null $query = null, string $menu = 'mainmenu'): bool
    {
        if (is_array($path)) {
            foreach ($path as $item) {
                if ($this->isActive($item)) {
                    return true;
                }
            }

            return false;
        }

        $matched = $this->getMatchedRoute();

        if (!$matched) {
            return false;
        }

        $routeName = $matched->getName();
        $shortName = Collection::explode('::', $matched->getName())->last();

        // Step (1): Match route with wildcards
        if (str_contains($path, '*')) {
            $path2 = ltrim($path, '/');

            $hasMatch = fnmatch($path2, $routeName)
                || fnmatch($path2, $shortName)
                || fnmatch($path2, $this->getSystemUri()->route());

            if ($hasMatch) {
                return true;
            }
        }

        // Step (2): Match ns::route
        if ($path === $routeName && str_contains($path, '::') && $this->matchVars($query)) {
            return true;
        }

        // Step (3): Match route without ns
        if ($path === $shortName && $this->matchVars($query)) {
            return true;
        }

        $menuDirect = $matched->getExtraValue('menu')[$menu] ?? null;

        // Step (4): If route not matched, we match extra values from routing.
        if ($menuDirect) {
            if ($menuDirect === $path && $this->matchVars($query)) {
                return true;
            }

            if (str_contains($path, '::')) {
                $path2 = explode('::', $path, 2);

                if (array_pop($path2) === $menuDirect && $this->matchVars($query)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function inGroup(string|array $groups, array|null $query = null): bool
    {
        $groups = TypeCast::toArray($groups);

        $matched = $this->getMatchedRoute();

        if (!$matched) {
            return false;
        }

        $currentGroups = array_keys($matched->getExtraValue('groups') ?? []);

        $active = array_intersect($groups, $currentGroups) !== [];

        return $active && $this->matchVars($query);
    }

    protected function matchVars(array|null $query = [], ?array $vars = null): bool
    {
        if ($query === null) {
            return true;
        }

        $requests = $this->app->getAppRequest()->inputWithMethod();

        $vars ??= $this->self()->getQueryValues();

        foreach ($requests as $key => &$request) {
            if (is_array($request) && in_array($key, $vars, true)) {
                $request = implode('/', $request);
            }
        }

        return !empty(Arr::query([$requests], $query));
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
        // Find by view class
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

    public function has(string $route): bool
    {
        return $this->findRoute($route) !== null;
    }

    /**
     * @return  SystemUri
     */
    public function getSystemUri(): SystemUri
    {
        return $this->app->getSystemUri();
    }
}
