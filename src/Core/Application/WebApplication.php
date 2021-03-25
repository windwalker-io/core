<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Application;

use JetBrains\PhpStorm\NoReturn;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Relay\Relay;
use Windwalker\Core\Controller\ControllerDispatcher;
use Windwalker\Core\Events\AfterBootEvent;
use Windwalker\Core\Events\Web\AfterRequestEvent;
use Windwalker\Core\Events\Web\BeforeRequestEvent;
use Windwalker\Core\Provider\AppProvider;
use Windwalker\Core\Provider\RequestProvider;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\Http\Output\Output;
use Windwalker\Http\Request\ServerRequestFactory;
use Windwalker\Http\Response\HtmlResponse;
use Windwalker\Http\Response\RedirectResponse;

/**
 * The WebApplication class.
 *
 * @since  __DEPLOY_VERSION__
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

        $container->registerByConfig($this->config('di') ?? []);

        foreach (iterator_to_array($this->config) as $service => $config) {
            if (!is_array($config)) {
                continue;
                // throw new \LogicException("Config: '{$service}' must be array");
            }

            $container->registerByConfig($config ?: []);
        }

        // Middlewares
        $middlewares = $this->config('middlewares') ?? [];

        foreach ($middlewares as $middleware) {
            $this->addMiddleware($middleware);
        }

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
    public function addMiddleware(mixed $middleware)
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * compileMiddlewares
     *
     * @param  Container  $container
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    protected function compileMiddlewares(Container $container): array
    {
        $queue = [];

        foreach ($this->middlewares as $middleware) {
            $queue[] = $container->resolve($middleware);
        }

        return $queue;
    }

    public function execute(?ServerRequestInterface $request, ?callable $handler = null): ResponseInterface
    {
        $this->boot();

        $request ??= ServerRequestFactory::createFromGlobals();

        $container = $this->getContainer()->createChild();
        $container->registerServiceProvider(new RequestProvider($request, $this));

        if ($handler) {
            $container->modify(
                AppContext::class,
                fn(AppContext $context): AppContext => $context->withController($handler)
            );
        }

        $this->registerListeners($container);

        $middlewares   = $this->compileMiddlewares($container);
        $middlewares[] = fn(ServerRequestInterface $request) => $container->get(ControllerDispatcher::class)
            ->dispatch($container->get(AppContext::class));

        // @event
        $event = $this->emit(
            BeforeRequestEvent::class,
            compact('container', 'middlewares', 'request')
        );

        $response = static::createRequestHandler($event->getMiddlewares())
            ->handle($event->getRequest());

        // @event
        $event = $this->emit(
            AfterRequestEvent::class,
            compact('container', 'response')
        );

        return $event->getResponse();
    }

    public static function createRequestHandler(iterable $queue): RequestHandlerInterface
    {
        return new Relay($queue);
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
    }

    protected function terminating(Container $container): void
    {
        //
    }
}
