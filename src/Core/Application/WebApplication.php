<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Application;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Relay\Relay;
use Windwalker\Core\Controller\ControllerDispatcher;
use Windwalker\Core\Provider\AppProvider;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\Http\Response\Response;

/**
 * The WebApplication class.
 *
 * @property-read Config $config
 *
 * @since  __DEPLOY_VERSION__
 */
class WebApplication implements ApplicationInterface
{
    use ApplicationTrait;

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
     * @param  callable|MiddlewareInterface  $middleware
     *
     * @return  $this
     */
    public function addMiddleware(MiddlewareInterface|callable $middleware)
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $this->boot();

        $this->getContainer()->share(ServerRequestInterface::class, $request);

        $queue = $this->middlewares;

        $queue[] = fn (ServerRequestInterface $request) => $this->service(ControllerDispatcher::class)
            ->dispatch($request);

        $relay = new Relay($queue);

        return $relay->handle($request);
    }

    protected function doExecute(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->service(ControllerDispatcher::class)->dispatch($request);
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
}
