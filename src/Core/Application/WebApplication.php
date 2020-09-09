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
use Relay\Relay;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\BootableDeferredProviderInterface;
use Windwalker\DI\BootableProviderInterface;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\DI\ServiceAwareTrait;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Event\EventEmitterInterface;
use Windwalker\Event\EventListenableInterface;
use Windwalker\Event\EventAwareTrait;
use Windwalker\Http\Response\Response;
use Windwalker\Utilities\Assert\Assert;

use function Windwalker\DI\share;

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
     * @param Container $container
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
        $container->share(Config::class, $container->getParameters());
        $container->share(Container::class, $container);
        $container->share(static::class, $this);
        $container->share(ApplicationInterface::class, $this);

        static::prepareDependencyInjection($this->config('di') ?? [], $this->getContainer());

        foreach ($this->config as $service => $config) {
            if (!is_array($config)) {
                throw new \LogicException("Config: '{$service}' must be array");
            }

            static::prepareDependencyInjection($config ?: [], $this->getContainer());
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

        $queue = $this->middlewares;

        $queue[] = \Closure::fromCallable([$this, 'doExecute']);

        $relay = new Relay($queue);

        return $relay->handle($request);
    }

    protected function doExecute(): ResponseInterface
    {
        return Response::fromString('Hello');
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
