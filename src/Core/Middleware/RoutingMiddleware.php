<?php

declare(strict_types=1);

namespace Windwalker\Core\Middleware;

use Psr\Container\ContainerExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\MiddlewareRunner;
use Windwalker\Core\Controller\DefaultController;
use Windwalker\Core\Event\CoreEventAwareTrait;
use Windwalker\Core\Events\Web\AfterRoutingEvent;
use Windwalker\Core\Events\Web\BeforeRoutingEvent;
use Windwalker\Core\Events\Web\RoutingNotMatchedEvent;
use Windwalker\Core\Router\Exception\RouteNotFoundException;
use Windwalker\Core\Router\Exception\UnAllowedMethodException;
use Windwalker\Core\Router\Route;
use Windwalker\Core\Router\Router;
use Windwalker\DI\DICreateTrait;
use Windwalker\DI\Exception\DefinitionResolveException;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Utilities\Options\OptionAccessTrait;
use Windwalker\WebSocket\Router\WsRouter;

/**
 * The RoutingMiddleware class.
 */
class RoutingMiddleware implements MiddlewareInterface, EventAwareInterface
{
    use OptionAccessTrait;
    use CoreEventAwareTrait;
    use DICreateTrait;

    protected bool $booted = false;

    /**
     * RoutingMiddleware constructor.
     *
     * @param  AppContext     $app
     * @param  WsRouter       $router
     * @param  \Closure|null  $preMatch
     * @param  \Closure|null  $fallback
     * @param  array          $options
     */
    public function __construct(
        protected AppContext $app,
        protected Router $router,
        protected ?\Closure $preMatch = null,
        protected ?\Closure $fallback = null,
        protected bool $methodOverride = true,
        /**
         * @deprecated  Use constructor arguments instead.
         */
        array $options = []
    ) {
        $this->prepareOptions(
            [
                'method_override' => $this->methodOverride,
            ],
            $options
        );

        $this->getEventDispatcher()->addDealer($app->getEventDispatcher());
    }

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     *
     * @param  ServerRequestInterface   $request
     * @param  RequestHandlerInterface  $handler
     *
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws \ReflectionException
     * @throws DefinitionResolveException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->boot();

        $router = $this->router;

        $route = $this->app->getSystemUri()->route;

        $event = $this->emit(
            new BeforeRoutingEvent(
                systemUri: $this->app->getSystemUri(),
                request: $request,
                route: $route
            )
        );

        $matched = null;

        try {
            if ($this->preMatch) {
                $matched = $this->app->call(
                    $this->fallback,
                    [
                        'request' => $request,
                        ServerRequestInterface::class => $request,
                        'router' => $router,
                        Router::class => $router,
                        'route' => $route,
                    ]
                );
            }

            if (!$matched) {
                $matched = $router->match($event->request, $event->route);
            }
        } catch (RouteNotFoundException $e) {
            if (!$this->fallback) {
                throw $e;
            }

            try {
                $matched = $this->app->call(
                    $this->fallback,
                    [
                        'request' => $request,
                        ServerRequestInterface::class => $event->request,
                        'router' => $router,
                        Router::class => $router,
                        'route' => $event->route,
                    ]
                );
            } catch (RouteNotFoundException $e2) {
                $e = $e2;
            }

            if (!$matched) {
                $event = $this->emit(
                    new RoutingNotMatchedEvent(
                        systemUri: $event->systemUri,
                        request: $request,
                        route: $event->route,
                        exception: $e
                    )
                );

                throw $event->exception;
            }
        }

        $event = $this->emit(
            new AfterRoutingEvent(
                systemUri: $event->systemUri,
                request: $request,
                route: $event->route,
                matched: $matched
            )
        );

        $override = (bool) $this->getOption('method_override');

        $method = $override
            ? $this->app->getRequestMethod()
            : $this->app->getRequestRawMethod();

        $controller = $this->findController($method, $matched = $event->matched);

        $this->app->getContainer()->modify(
            AppContext::class,
            fn(AppContext $context) => $context->setController($controller)
                ->setMatchedRoute($matched)
        );

        $subscribers = $matched->getSubscribers();

        if ($subscribers !== []) {
            $this->app->handleListeners($subscribers, $this->app->getContainer());
        }

        $middlewares = $matched->getMiddlewares();

        return $this->app->make(MiddlewareRunner::class)
            ->run(
                $request,
                $middlewares,
                fn(ServerRequestInterface $request) => $handler->handle($request)
            );
    }

    /**
     * @param  string  $method
     * @param  Route   $route
     *
     * @return  mixed
     */
    protected function findController(string $method, Route $route): mixed
    {
        $method = strtolower($method);

        $handlers = $route->getHandlers();

        if (isset($handlers[$method])) {
            $handler = $handlers[$method];

            if (is_string($handler)) {
                $handler = [$handlers['*'], $handler];
            }
        } else {
            $handler = $handlers['*'] ?? null;
        }

        if (!$handler && $method === 'get') {
            $handler = DefaultController::class;
        }

        if (!$handler) {
            throw new UnAllowedMethodException(
                sprintf(
                    'Handler for method: "%s" not found in route: "%s".',
                    $method,
                    $route->getName()
                )
            );
        }

        return $handler;
    }

    /**
     * @return  void
     */
    protected function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->addEventDealer($this->router);
        $this->addEventDealer($this->app);

        $this->booted = true;
    }
}
