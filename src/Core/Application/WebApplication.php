<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 .
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Application;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Relay\Relay;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\BootableDeferredProviderInterface;
use Windwalker\DI\BootableProviderInterface;
use Windwalker\DI\Container;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\Http\Response\Response;
use Windwalker\Utilities\Assert\Assert;
use Windwalker\Utilities\Classes\OptionAccessTrait;

use function Windwalker\DI\share;

/**
 * The WebApplication class.
 *
 * @since  __DEPLOY_VERSION__
 */
class WebApplication
{
    protected bool $booted = false;

    protected Container $container;

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

        $this->prepareDependencyInjection();

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

    protected function prepareDependencyInjection(): void
    {
        $container = $this->getContainer();

        $this->prepareBindings($container);
        $this->prepareProviders($container);
        $this->prepareDIAliases($container);
    }

    protected function prepareBindings(Container $container): void
    {
        foreach ($this->config('di.binding') ?? [] as $key => $value) {
            if (is_numeric($key)) {
                if (!is_string($value)) {
                    throw new DefinitionException(
                        sprintf(
                            'Binding classes must with a string key, %s given.',
                            Assert::describeValue($value)
                        )
                    );
                }

                $container->set($value, share($value));
            } else {
                $container->set($key, $value);
            }
        }
    }

    protected function prepareProviders(Container $container): void
    {
        $bootDeferred = [];

        foreach ($this->config('di.providers') ?? [] as $provider) {
            if (is_string($provider)) {
                $provider = $container->newInstance($provider);
            }

            $container->registerServiceProvider($provider);

            if ($provider instanceof BootableProviderInterface) {
                $provider->boot($container);
            }

            if ($provider instanceof BootableDeferredProviderInterface) {
                $bootDeferred[] = $provider;
            }
        }

        foreach ($bootDeferred as $provider) {
            $provider->bootDeferred($container);
        }
    }

    protected function prepareDIAliases(Container $container): void
    {
        foreach ($this->config('di.aliases') ?? [] as $alias => $id) {
            $container->alias($alias, $id);
        }
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
