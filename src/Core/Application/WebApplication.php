<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

use JetBrains\PhpStorm\NoReturn;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Stringable;
use Throwable;
use Windwalker\Core\Controller\ControllerDispatcher;
use Windwalker\Core\DI\RequestBootableProviderInterface;
use Windwalker\Core\DI\RequestReleasableProviderInterface;
use Windwalker\Core\Events\Web\AfterRequestEvent;
use Windwalker\Core\Events\Web\AfterRespondEvent;
use Windwalker\Core\Events\Web\BeforeAppDispatchEvent;
use Windwalker\Core\Events\Web\BeforeRequestEvent;
use Windwalker\Core\Events\Web\TerminatingEvent;
use Windwalker\Core\Form\Exception\ValidateFailException;
use Windwalker\Core\Profiler\ProfilerFactory;
use Windwalker\Core\Provider\AppProvider;
use Windwalker\Core\Provider\RequestProvider;
use Windwalker\Core\Provider\WebProvider;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Security\Exception\InvalidTokenException;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\Http\Event\RequestEvent;
use Windwalker\Http\Output\Output;
use Windwalker\Http\Output\OutputInterface;
use Windwalker\Http\Output\StreamOutput;
use Windwalker\Http\Request\ServerRequest;
use Windwalker\Http\Response\HtmlResponse;
use Windwalker\Http\Response\RedirectResponse;

/**
 * The WebApplication class.
 *
 * @since  4.0
 */
class WebApplication implements WebApplicationInterface, RootApplicationInterface
{
    use WebApplicationTrait;

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

        if ($this->getClientType() === 'web') {
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
        $container->registerServiceProvider(new WebProvider($this));

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

    public function createContext(?ServerRequestInterface $request = null): AppContext
    {
        $this->boot();

        // Level 2
        $container = $this->getContainer();

        // Level 3
        $container = $container->createChild();

        if ($request !== null) {
            $container->share(ServerRequest::class, $request);
        }

        $this->registerListeners($container);

        $this->bootProvidersBeforeRequest($container);

        return $container->get(AppContext::class);
    }

    public function createContextFromServerEvent(RequestEvent $event): AppContext
    {
        $request = $event->getRequest();

        $appContext = $this->createContext($request);

        $container = $appContext->getContainer();
        $container->share(OutputInterface::class, $event->getOutput());

        $event->setAttribute('appContext', $appContext);

        return $appContext;
    }

    public function runContext(AppContext $appContext, ?callable $handler = null): ResponseInterface
    {
        $container = $appContext->getContainer();
        $request = $container->get(ServerRequestInterface::class);

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
                ->dispatch($container->get(AppContext::class))
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

        return $event->getResponse();
    }

    public function executeServerEvent(RequestEvent $event): ResponseInterface
    {
        return $this->runContext(
            $this->createContextFromServerEvent($event)
        );
    }

    public function execute(?ServerRequestInterface $request = null, ?callable $handler = null): ResponseInterface
    {
        $appContext = $this->createContext($request);

        $container = $appContext->getContainer();
        $container->bindShared(OutputInterface::class, fn() => new StreamOutput());

        register_shutdown_function(
            fn() => $this->stopContext($appContext)
        );

        return $this->runContext($appContext);
    }

    public function stopContext(AppContext $appContext): void
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
     * @param  ServerRequestInterface  $request
     * @param  iterable                $middlewares
     *
     * @return ResponseInterface
     */
    protected function dispatch(
        AppContext $app,
        ServerRequestInterface $request,
        iterable $middlewares
    ): mixed {
        $runner = $app->make(MiddlewareRunner::class);

        try {
            return $runner->createRequestHandler($middlewares)
                ->handle($request);
        } catch (ValidateFailException | InvalidTokenException $e) {
            if ($app->isDebug()) {
                throw $e;
            }

            $app->addMessage($e->getMessage(), 'warning');
            $nav = $app->service(Navigator::class);

            return $this->redirect($nav->back());
        } catch (Throwable $e) {
            if (
                $app->isDebug()
                || strtoupper($app->getRequestMethod()) === 'GET'
                || $app->getAppRequest()->isAcceptJson()
            ) {
                throw $e;
            }

            $app->addMessage($e->getMessage(), 'warning');
            $nav = $app->service(Navigator::class);

            return $this->redirect($nav->back());
        }
    }

    /**
     * Redirect to another URL.
     *
     * @param  string|Stringable  $url
     * @param  int                $code
     * @param  bool               $instant
     *
     * @return  ResponseInterface
     */
    public function redirect(string|Stringable $url, int $code = 303, bool $instant = false): ResponseInterface
    {
        // Perform a basic sanity check to make sure we don't have any CRLF garbage.
        $url = preg_split("/[\r\n]/", (string) $url)[0];

        // If the headers have already been sent we need to send the redirect statement via JavaScript.
        if ($this->checkHeadersSent()) {
            $res = HtmlResponse::fromString("<script>document.location.href='$url';</script>\n");
        } else {
            $res = RedirectResponse::fromString($url, $code);
        }

        if ($instant) {
            $this->container->newInstance(Output::class)->respond($res);
            // Close the application after the redirect.
            $this->close();
        }

        return $res;
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
        $container->registerServiceProvider(new RequestProvider($container));

        foreach ($this->providers as $provider) {
            if ($provider instanceof RequestBootableProviderInterface) {
                $provider->bootBeforeRequest($container);
            }
        }
    }

    protected function releaseProviders(Container $container): void
    {
        foreach ($this->providers as $provider) {
            if ($provider instanceof RequestBootableProviderInterface) {
                $provider->bootBeforeRequest($container);
            }
        }
    }

    public function addMessage(array|string $messages, ?string $type = 'info'): static
    {
        return $this;
    }
}
