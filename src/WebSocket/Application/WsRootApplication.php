<?php

declare(strict_types=1);

namespace Windwalker\WebSocket\Application;

use JetBrains\PhpStorm\NoReturn;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Windwalker\Core\Application\AppType;
use Windwalker\Core\Application\MiddlewareRunner;
use Windwalker\Core\Application\WebApplicationTrait;
use Windwalker\Core\CliServer\CliServerClient;
use Windwalker\Core\Controller\ControllerDispatcher;
use Windwalker\Core\DI\RequestBootableProviderInterface;
use Windwalker\Core\DI\RequestReleasableProviderInterface;
use Windwalker\Core\Events\Web\AfterRequestEvent;
use Windwalker\Core\Events\Web\AfterRespondEvent;
use Windwalker\Core\Events\Web\BeforeAppDispatchEvent;
use Windwalker\Core\Events\Web\BeforeRequestEvent;
use Windwalker\Core\Events\Web\TerminatingEvent;
use Windwalker\Core\Profiler\ProfilerFactory;
use Windwalker\Core\Provider\AppProvider;
use Windwalker\Core\Provider\RequestProvider;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\Http\Server\HttpServerInterface;
use Windwalker\Http\Server\ServerInterface;
use Windwalker\Reactor\WebSocket\WebSocketFrame;
use Windwalker\Reactor\WebSocket\WebSocketRequest;
use Windwalker\Reactor\WebSocket\WebSocketRequestInterface;
use Windwalker\Reactor\WebSocket\WebSocketServerInterface;
use Windwalker\Uri\Uri;
use Windwalker\WebSocket\Provider\WebSocketProvider;

/**
 * The WebSocketApplication class.
 */
class WsRootApplication implements WsRootApplicationInterface
{
    use WebApplicationTrait;
    use WsApplicationTrait;

    protected bool $booted = false;

    /**
     * @var MiddlewareInterface[]|callable[]
     */
    protected array $middlewares = [];

    /**
     * WebApplication constructor.
     *
     * @param  Container  $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function bootForServer(ServerInterface $server): void
    {
        $container = $this->getContainer();

        $container->share($server::class, $server)
            ->alias(ServerInterface::class, $server::class)
            ->alias(HttpServerInterface::class, $server::class)
            ->alias(WebSocketServerInterface::class, $server::class);

        $this->boot();
    }

    /**
     * boot
     *
     * @return  void
     * @throws DefinitionException
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Start profiler for Web type
        $profilerFactory = null;

        if ($this->getType() !== AppType::CONSOLE) {
            $profilerFactory = new ProfilerFactory();
            $profilerFactory->get('main')->mark('boot', ['system']);
        }

        $this->prepareBoot();

        // Prepare child
        $container = $this->getContainer();
        $container->registerServiceProvider(new AppProvider($this, $profilerFactory));

        $this->registerAllConfigs($container);

        // Middlewares
        $middlewares = $this->config('middlewares') ?? [];

        foreach ($middlewares as $middleware) {
            $this->addMiddleware($middleware);
        }

        // Request provider
        $container->registerServiceProvider(new WebSocketProvider($this));

        $this->booting($container->createChild());

        $this->booted = true;
    }

    /**
     * Your booting logic.
     *
     * @param  Container  $container
     *
     * @return  void
     */
    protected function booting(Container $container): void
    {
        //
    }

    /**
     * addMiddleware
     *
     * @param  mixed  $middleware
     *
     * @return  $this
     */
    public function addMiddleware(mixed $middleware): static
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    public function createContext(WebSocketRequestInterface $request): WsAppContext
    {
        $this->boot();

        // Level 2
        $container = $this->getContainer();

        // Level 3
        $container = $container->createChild();

        // if ($request !== null) {
        $container->share(WebSocketRequest::class, $request);
        // }

        $this->registerListeners($container);

        $this->bootProvidersBeforeRequest($container);

        return $container->get(WsAppContext::class);
    }

    public function createContextWithTask(
        string $task,
        array $input = [],
        ?WebSocketRequestInterface $request = null
    ): WsAppContext {
        if (!$request) {
            $frame = new WebSocketFrame(0, '');
            $request = WebSocketRequest::createFromFrame($frame);
        }

        $request = $request->withUri((new Uri())->withPath($task))
            ->withParsedBody($input);

        return $this->createContext($request);
    }

    /**
     * Run this appContext and return the response.
     *
     * NOTE: This method will not emit any content to output, you must emit response yourself.
     *
     * @param  WsAppContext   $appContext
     * @param  callable|null  $handler
     *
     * @return  ResponseInterface
     *
     * @throws Throwable
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function runContext(WsAppContext $appContext, ?callable $handler = null): ResponseInterface
    {
        $start = microtime(true);

        $container = $appContext->getContainer();
        $request = $container->get(WebSocketRequestInterface::class);

        $client = $appContext->service(CliServerClient::class);
        $client->logWebSocketMessageStart($request);

        // @event
        $event = $this->emit(
            BeforeRequestEvent::class,
            compact('container', 'request')
        );

        if ($handler) {
            $appContext->setController($handler);
        }

        // @event
        $event = $appContext->emit(
            BeforeRequestEvent::class,
            ['container' => $container, 'request' => $event->getRequest()]
        );

        $request = $event->getRequest();

        $middlewares = MiddlewareRunner::chainMiddlewares(
            $this->middlewares,
            static fn(ServerRequestInterface $request) => $container->get(ControllerDispatcher::class)
                ->dispatch($container->get(WsAppContext::class))
        );

        // @event
        $event = $appContext->emit(
            BeforeAppDispatchEvent::class,
            compact('container', 'middlewares', 'request')
        );

        $response = $this->dispatch($appContext, $event->getRequest(), $event->getMiddlewares());

        // @event
        $event = $this->emit(
            AfterRequestEvent::class,
            compact('container', 'response')
        );

        // @event
        $event = $appContext->emit(
            AfterRequestEvent::class,
            ['container' => $container, 'response' => $event->getResponse()]
        );

        $client->logWebSocketMessageEnd(
            $request,
            (int) round((microtime(true) - $start) * 1000)
        );

        return $event->getResponse();
    }

    public function stopContext(WsAppContext $appContext): void
    {
        $container = $appContext->getContainer();

        $appContext->emit(
            AfterRespondEvent::class,
            compact('container')
        );

        foreach ($this->providers as $provider) {
            if ($provider instanceof RequestReleasableProviderInterface) {
                $provider->releaseAfterRequest($container);
            }
        }
    }

    /**
     * @param  WsAppContext               $app
     * @param  WebSocketRequestInterface  $request
     * @param  iterable                   $middlewares
     *
     * @return ResponseInterface
     */
    protected function dispatch(
        WsAppContext $app,
        WebSocketRequestInterface $request,
        iterable $middlewares
    ): mixed {
        $runner = $app->make(MiddlewareRunner::class);

        // Todo: Handle errors
        return $runner->createRequestHandler($middlewares)
            ->handle($request);

        // try {
        //     return $runner->createRequestHandler($middlewares)
        //         ->handle($request);
        // } catch (ValidateFailException | InvalidTokenException $e) {
        //     if ($app->isDebug()) {
        //         throw $e;
        //     }
        //
        //     $app->addMessage($e->getMessage(), 'warning');
        //     $nav = $app->service(Navigator::class);
        //
        //     return $this->redirect($nav->back());
        // } catch (\Throwable $e) {
        //     if (
        //         $app->isDebug()
        //         || strtoupper($app->getRequestMethod()) === 'GET'
        //         || $app->getAppRequest()->isAcceptJson()
        //     ) {
        //         throw $e;
        //     }
        //
        //     $app->addMessage($e->getMessage(), 'warning');
        //     $nav = $app->service(Navigator::class);
        //
        //     return $this->redirect($nav->back());
        // }
    }

    /**
     * Close this request.
     *
     * @param  mixed  $return
     *
     * @return  void
     */
    #[NoReturn]
    public function close(mixed $return = ''): void
    {
        die($return);
    }

    public function terminate(): void
    {
        $this->terminating($this->getContainer());

        $this->emit(
            TerminatingEvent::class,
            [
                'app' => $this,
                'container' => $this->getContainer(),
            ]
        );
    }

    protected function terminating(Container $container): void
    {
        //
    }

    /**
     * bootProvidersBeforeRequest
     *
     * @param  Container  $container
     *
     * @return  void
     */
    protected function bootProvidersBeforeRequest(Container $container): void
    {
        $container->registerServiceProvider(new RequestProvider());

        foreach ($this->providers as $provider) {
            if ($provider instanceof RequestBootableProviderInterface) {
                $provider->bootBeforeRequest($container);
            }
        }
    }

    protected function releaseProviders(Container $container): void
    {
        foreach ($this->providers as $provider) {
            if ($provider instanceof RequestReleasableProviderInterface) {
                $provider->releaseAfterRequest($container);
            }
        }
    }

    public function addMessage(array|string $messages, ?string $type = 'info'): static
    {
        return $this;
    }

    public function openConnection(WebSocketRequestInterface $request): void
    {
        $start = microtime(true);

        $this->opening($request);

        $client = $this->service(CliServerClient::class);
        $client->logWebSocketOpen($request, (microtime(true) - $start) * 1000);
    }

    public function closeConnection(WebSocketRequestInterface $request): void
    {
        $start = microtime(true);

        $this->closing($request);

        $client = $this->service(CliServerClient::class);
        $client->logWebSocketClose($request, (microtime(true) - $start) * 1000);
    }

    public function initialize(): void
    {
        $this->init();
    }

    protected function init(): void
    {
        //
    }

    public function start(): void
    {
        $this->started();
    }

    protected function started(): void
    {
        //
    }

    protected function opening(WebSocketRequestInterface $request)
    {
        //
    }

    protected function closing(WebSocketRequestInterface $request)
    {
        //
    }
}
