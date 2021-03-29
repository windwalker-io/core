<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Router;

use FastRoute\RouteParser\Std;
use Psr\Http\Message\ResponseInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Router\Event\BeforeRouteBuildEvent;
use Windwalker\Core\Router\Exception\RouteNotFoundException;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Event\EventAwareTrait;
use Windwalker\Event\EventEmitter;

/**
 * The Navigator class.
 */
class Navigator implements NavConstantInterface, EventAwareInterface
{
    use EventAwareTrait;

    /**
     * @var RouteBuilder
     */
    protected RouteBuilder $routeBuilder;

    protected int $options = 0;

    public function __construct(
        protected AppContext $app,
        protected Router $router,
        EventEmitter $dispatcher,
        ?RouteBuilder $routeBuilder = null
    ) {
        $this->routeBuilder = $routeBuilder ?? new RouteBuilder(new Std());
        $this->dispatcher   = $dispatcher;
    }

    public function to(string $route, array $query = [], int $options = self::TYPE_PATH): RouteUri
    {
        $options |= $this->options;

        $navigator = $this;

        $event = $this->emit(
            BeforeRouteBuildEvent::class,
            compact('navigator', 'query', 'route', 'options')
        );

        $route = $event->getRoute();
        $options = $event->getOptions();
        $query = $event->getQuery();

        $handler = function (array $query) use ($route) {
            $routeObject = $this->router->getRoute($route);

            if (!$routeObject) {
                throw new RouteNotFoundException('Route: ' . $route . ' not found.');
            }

            return $this->routeBuilder->build($routeObject->getPattern(), $query);
        };

        return new RouteUri($handler, $query, $this, $options);
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
}
