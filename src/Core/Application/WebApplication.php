<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Application;

use JetBrains\PhpStorm\ExitPoint;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Relay\Relay;
use Windwalker\Core\Controller\ControllerDispatcher;
use Windwalker\Core\Provider\AppProvider;
use Windwalker\Core\Provider\RequestProvider;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\Http\Output\Output;
use Windwalker\Http\Response\HtmlResponse;
use Windwalker\Http\Response\RedirectResponse;

/**
 * The WebApplication class.
 *
 * @property-read Config $config
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
     *
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

        foreach ($this->config as $service => $config) {
            if (!is_array($config)) {
                throw new \LogicException("Config: '{$service}' must be array");
            }

            $container->registerByConfig($config ?: []);
        }

        // Middlewares
        $middlewares = $this->config('middlewares') ?? [];

        foreach ($middlewares as $middleware) {
            $this->addMiddleware($middleware);
        }

        $this->booted = true;
    }

    /**
     * Method to get property Container
     *
     * @return  Container
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * loadConfig
     *
     * @param  mixed        $source
     * @param  string|null  $format
     * @param  array        $options
     *
     * @return  void
     */
    public function loadConfig($source, ?string $format = null, array $options = []): void
    {
        $this->getContainer()->loadParameters($source, $format, $options);
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

    public function execute(ServerRequestInterface $request, ?callable $handler = null): ResponseInterface
    {
        $this->boot();

        $subContainer = $this->getContainer()->createChild();
        $subContainer->registerServiceProvider(new RequestProvider($request));

        if ($handler) {
            $subContainer->modify(
                AppContext::class,
                fn(AppContext $context): AppContext => $context->withController($handler)
            );
        }

        $queue   = $this->compileMiddlewares($subContainer);
        $queue[] = fn(ServerRequestInterface $request) => $subContainer->get(ControllerDispatcher::class)
            ->dispatch($subContainer->get(AppContext::class));

        return static::createRequestHandler($queue)
            ->handle($request);
    }

    public static function createRequestHandler(iterable $queue): RequestHandlerInterface
    {
        return new Relay($queue);
    }

    /**
     * config
     *
     * @param  string       $name
     * @param  string|null  $delimiter
     *
     * @return  mixed
     */
    public function config(string $name, ?string $delimiter = '.')
    {
        return $this->getContainer()->getParameters()->getDeep($name, $delimiter);
    }

    public function __get(string $name)
    {
        if ($name === 'config') {
            return $this->getContainer()->getParameters();
        }

        if ($name === 'container') {
            return $this->getContainer();
        }

        throw new \OutOfRangeException('No such property: ' . $name . ' in ' . static::class);
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
    #[ExitPoint]
    public function close(mixed $return = ''): void
    {
        die($return);
    }
}
