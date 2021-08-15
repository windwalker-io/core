<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    MIT
 */

namespace Windwalker\Core\Application;

use JetBrains\PhpStorm\NoReturn;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Windwalker\Core\Controller\ControllerDispatcher;
use Windwalker\Core\DI\RequestBootableProviderInterface;
use Windwalker\Core\Events\Web\AfterRequestEvent;
use Windwalker\Core\Events\Web\BeforeAppDispatchEvent;
use Windwalker\Core\Events\Web\BeforeRequestEvent;
use Windwalker\Core\Events\Web\TerminatingEvent;
use Windwalker\Core\Form\Exception\ValidateFailException;
use Windwalker\Core\Provider\AppProvider;
use Windwalker\Core\Provider\WebProvider;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Security\Exception\InvalidTokenException;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\Http\Output\Output;
use Windwalker\Http\Request\ServerRequest;
use Windwalker\Http\Response\HtmlResponse;
use Windwalker\Http\Response\RedirectResponse;

/**
 * The WebApplication class.
 *
 * @since  4.0.0-beta1
 */
class WebApplication implements WebApplicationInterface
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

        // Prepare child
        $container = $this->getContainer();
        $container->registerServiceProvider(new AppProvider($this));

        $this->registerAllConfigs($container);

        // Middlewares
        $middlewares = $this->config('middlewares') ?? [];

        foreach ($middlewares as $middleware) {
            $this->addMiddleware($middleware);
        }

        // Request provider
        $container->registerServiceProvider(new WebProvider($this));

        $this->booting($container->createChild());

        $container->clearCache();

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

    public function execute(?ServerRequestInterface $request = null, ?callable $handler = null): ResponseInterface
    {
        $this->boot();

        // Level 3
        $container = $this->getContainer()->createChild();

        if ($request !== null) {
            $container->share(ServerRequest::class, $request);
        }

        $request ??= $container->get(ServerRequestInterface::class);

        $this->bootProvidersBeforeRequest($container);

        // @event
        $event = $this->emit(
            BeforeRequestEvent::class,
            compact('container', 'request')
        );

        $request = $event->getRequest();

        if ($handler) {
            $container->modify(
                AppContext::class,
                fn(AppContext $context): AppContext => $context->setController($handler)
            );
        }

        $this->registerListeners($container);

        $runner = $container->newInstance(MiddlewareRunner::class);

        $middlewares = $runner->compileMiddlewares(
            $this->middlewares,
            fn(ServerRequestInterface $request) => $container->get(ControllerDispatcher::class)
                ->dispatch($container->get(AppContext::class))
        );

        $appContext = $container->get(AppContext::class);

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

        return $event->getResponse();
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
        try {
            return MiddlewareRunner::createRequestHandler($middlewares)
                ->handle($request);
        } catch (ValidateFailException|InvalidTokenException $e) {
            if ($app->isDebug()) {
                throw $e;
            }

            $app->addMessage($e->getMessage(), 'warning');
            $nav = $app->service(Navigator::class);

            return $this->redirect($nav->back());
        } catch (\Throwable $e) {
            if ($app->isDebug()
                || strtoupper($app->getRequestMethod()) === 'GET'
                || $app->getAppRequest()->acceptJson()
            ) {
                throw $e;
            } else {
                $app->addMessage($e->getMessage(), 'warning');
                $nav = $app->service(Navigator::class);

                return $this->redirect($nav->back());
            }
        }
    }

    /**
     * Redirect to another URL.
     *
     * @param  string|\Stringable  $url
     * @param  int                 $code
     * @param  bool                $instant
     *
     * @return  ResponseInterface
     */
    public function redirect(string|\Stringable $url, int $code = 303, bool $instant = false): ResponseInterface
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
        foreach ($this->providers as $provider) {
            if ($provider instanceof RequestBootableProviderInterface) {
                $provider->bootBeforeRequest($container);
            }
        }
    }
}
