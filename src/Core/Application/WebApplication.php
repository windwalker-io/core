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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LogLevel;
use Stringable;
use Throwable;
use Windwalker\Core\CliServer\CliServerClient;
use Windwalker\Core\CliServer\CliServerRuntime;
use Windwalker\Core\Controller\ControllerDispatcher;
use Windwalker\Core\DI\RequestBootableProviderInterface;
use Windwalker\Core\DI\RequestReleasableProviderInterface;
use Windwalker\Core\Events\Web\AfterRequestEvent;
use Windwalker\Core\Events\Web\AfterRespondEvent;
use Windwalker\Core\Events\Web\BeforeAppDispatchEvent;
use Windwalker\Core\Events\Web\BeforeRequestEvent;
use Windwalker\Core\Events\Web\TerminatingEvent;
use Windwalker\Core\Form\Exception\ValidateFailException;
use Windwalker\Core\Module\ModuleInterface;
use Windwalker\Core\Profiler\ProfilerFactory;
use Windwalker\Core\Provider\AppProvider;
use Windwalker\Core\Provider\RequestProvider;
use Windwalker\Core\Provider\WebProvider;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Security\Exception\InvalidTokenException;
use Windwalker\Core\Service\ErrorService;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\DI\Exception\DefinitionResolveException;
use Windwalker\Http\Event\RequestEvent;
use Windwalker\Http\Helper\ResponseHelper;
use Windwalker\Http\Output\Output;
use Windwalker\Http\Output\OutputInterface;
use Windwalker\Http\Output\StreamOutput;
use Windwalker\Http\Request\ServerRequest;
use Windwalker\Http\Response\HtmlResponse;
use Windwalker\Http\Response\RedirectResponse;
use Windwalker\Http\Server\HttpServerInterface;
use Windwalker\Http\Server\ServerInterface;

/**
 * The WebApplication class.
 *
 * @since  4.0
 */
class WebApplication implements WebRootApplicationInterface
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

    public function bootForServer(ServerInterface $server): void
    {
        $container = $this->getContainer();

        $container->share($server::class, $server)
            ->alias(ServerInterface::class, $server::class)
            ->alias(HttpServerInterface::class, $server::class);

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

        if ($this->getType() === AppType::WEB) {
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

    /**
     * Create an AppContext for every request.
     *
     * @param  RequestEvent  $event
     *
     * @return  AppContext
     *
     * @throws DefinitionException
     */
    public function createContextFromServerEvent(RequestEvent $event): AppContext
    {
        $request = $event->getRequest();

        $appContext = $this->createContext($request);

        $container = $appContext->getContainer();
        $container->share(OutputInterface::class, $event->getOutput());

        $event->setAttribute('appContext', $appContext);

        return $appContext;
    }

    /**
     * Run this appContext and return the response.
     *
     * NOTE: This method will not emit any content to output, you must emit response yourself.
     *
     * @param  AppContext     $appContext
     * @param  callable|null  $handler
     *
     * @return  ResponseInterface
     *
     * @throws Throwable
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
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
            fn(ServerRequestInterface $request) => static::anyToResponse(
                $this->dispatchController($container)
            )
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

    public function runCliServerRequest(RequestEvent $event): RequestEvent
    {
        $client = $this->service(CliServerClient::class);

        $appContext = $this->createContextFromServerEvent($event);

        $start = microtime(true);

        try {
            $response = $this->runContext($appContext);
            $statusCode = $response->getStatusCode();

            $event->setResponse($response);
        } catch (\Throwable $e) {
            $statusCode = $e->getCode();

            if (!ResponseHelper::isClientError($statusCode)) {
                $output = CliServerRuntime::getStyledOutput();

                try {
                    $output->error("({$e->getCode()}) " . $e->getMessage());
                    $output->writeln('  # File: ' . $e->getFile() . ':' . $e->getLine());
                    $output->newLine(2);
                    $error = $appContext->service(ErrorService::class);

                    $error->handle($e);
                } catch (\Throwable $e) {
                    $this->log(
                        '[Infinite loop in error handling]: ' . $e->getMessage(),
                        level: LogLevel::ERROR
                    );
                }
            }
        } finally {
            $event->setEndHandler(fn () => $this->stopContext($appContext));

            $duration = round((microtime(true) - $start) * 1000);

            $client->logRequestInfo(
                $event->getRequest(),
                $statusCode,
                $duration
            );

            // Todo: Add RequestTerminateEvent
            // $this->terminate();
        }

        return $event;
    }

    public function executeServerEvent(RequestEvent $event): ResponseInterface
    {
        $appContext = $this->createContextFromServerEvent($event);

        $event->setEndHandler(fn () => $this->stopContext($appContext));

        return $this->runContext($appContext);
    }

    /**
     * Run for classic Apache/FPM one time request.
     *
     * @param  ServerRequestInterface|null  $request
     * @param  callable|null                $handler
     *
     * @return  ResponseInterface
     * @throws Throwable
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
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

        $this->releaseProviders($container);
    }

    protected function dispatchController(Container $container)
    {
        $appContext = $container->get(AppContext::class);

        try {
            return $appContext->dispatchController();
        } catch (ValidateFailException $e) {
            if ($this->isDebug() || $appContext->isAjax()) {
                throw $e;
            }

            $appContext->addMessage($e->getMessage(), 'warning');
            $nav = $appContext->service(Navigator::class);

            return $nav->back();
        } catch (Throwable $e) {
            if ($appContext->isCliRuntime()) {
                $appContext->logError($e);
            }

            if (
                $appContext->isDebug()
                || strtoupper($appContext->getRequestMethod()) === 'GET'
                || $appContext->getAppRequest()->isAcceptJson()
                || $appContext->isAjax()
            ) {
                throw $e;
            }

            $appContext->addMessage($e->getMessage(), 'warning');
            $nav = $appContext->service(Navigator::class);

            return $nav->back();
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
     * @throws \ReflectionException
     * @throws DefinitionResolveException
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
            if ($provider instanceof RequestReleasableProviderInterface) {
                $provider->releaseAfterRequest($container);
            }
        }
    }

    public function addMessage(array|string $messages, ?string $type = 'info'): static
    {
        return $this;
    }
}
