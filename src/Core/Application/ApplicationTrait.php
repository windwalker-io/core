<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

use Closure;
use OutOfRangeException;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionException;
use Windwalker\Attributes\AttributesAccessor;
use Windwalker\Core\Console\Process\ProcessRunnerTrait;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\BootableDeferredProviderInterface;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Event\Attributes\ListenTo;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Event\EventAwareTrait;
use Windwalker\Event\EventListenableInterface;

/**
 * Trait ApplicationTrait
 *
 * @property-read Config $config
 */
trait ApplicationTrait
{
    use ServiceAwareTrait {
        resolve as diResolve;
    }
    use EventAwareTrait;
    use ProcessRunnerTrait;

    /**
     * @var array<ServiceProviderInterface>
     */
    protected array $providers = [];

    public function getAppName(): string
    {
        return (string) ($this->config('app.name') ?? 'Windwalker');
    }

    /**
     * config
     *
     * @param  string       $name
     * @param  string|null  $delimiter
     *
     * @return  mixed
     */
    public function config(string $name, ?string $delimiter = '.'): mixed
    {
        return $this->getContainer()->getParam($name, $delimiter);
    }

    /**
     * Get system path.
     *
     * Example: $app->path(`@root/path/to/file`);
     *
     * @param  string  $path
     *
     * @return  string
     * @throws ReflectionException
     */
    public function path(string $path): string
    {
        $pathResolver = $this->resolve(PathResolver::class);

        return $pathResolver->resolve($path);
    }

    /**
     * isDebug
     *
     * @return  bool
     */
    public function isDebug(): bool
    {
        return $this->config('app.debug');
    }

    /**
     * getMode
     *
     * @return  string
     */
    public function getMode(): string
    {
        return $this->config('app.mode');
    }

    /**
     * Method to get property Container
     *
     * @return  Container
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
    public function loadConfig(mixed $source, ?string $format = null, array $options = []): void
    {
        $this->getContainer()->loadParameters($source, $format, $options);
    }

    protected function registerAllConfigs(Container $container): void
    {
        $container->registerByConfig($this->config('di') ?? [], $providers);

        foreach (iterator_to_array($this->config) as $service => $config) {
            if (!is_array($config) || !($config['enabled'] ?? true)) {
                continue;
            }

            $container->registerByConfig($config ?: [], $providers);
        }

        foreach ($providers as $provider) {
            if ($provider instanceof BootableDeferredProviderInterface) {
                $provider->bootDeferred($container);
            }
        }

        $this->providers = $providers;
    }

    protected function registerListeners(Container $container): void
    {
        $listeners = $this->config('listeners') ?? [];

        $this->handleListeners($listeners, $container);

        foreach (iterator_to_array($this->config) as $service => $config) {
            if (!($config['enabled'] ?? true)) {
                continue;
            }

            $listeners = $config['listeners'] ?? [];

            $this->handleListeners($listeners, $container);
        }
    }

    private function handleListener(
        Container $container,
        EventDispatcherInterface $dispatcher,
        string|int $name,
        mixed $listener
    ): void {
        if ($listener === null || $listener === false) {
            return;
        }

        if (is_numeric($name)) {
            if ($dispatcher instanceof EventListenableInterface) {
                if ($listener instanceof Closure) {
                    // Closure with ListenTo() attribute
                    $event = AttributesAccessor::getFirstAttributeInstance($listener, ListenTo::class);
                    $event->listen($dispatcher, $listener);
                } else {
                    // Simply listener class name.
                    $dispatcher->subscribe($container->resolve($listener));
                }
            }
        } elseif (is_callable($listener)) {
            // Events EventName => listener
            $dispatcher->on($name, $listener);
        } elseif (is_array($listener)) {
            if (isset($listener[1]) && $listener[1] === '__invoke') {
                // EventAwareObject name => Array<EventName, [object, __invoke]>
                $listener[0] = $container->resolve($listener[0]);
                $this->handleListener($container, $dispatcher, $name, $listener);
            } elseif ($container->has($name)) {
                // EventAwareObject name => Array<EventName, Listeners>
                $container->extend($name, function (EventAwareInterface $object) use ($name, $listener, $container) {
                    foreach ($listener as $eventName => $eventListener) {
                        $this->handleListener($container, $object->getEventDispatcher(), $eventName, $eventListener);
                    }

                    return $object;
                });
            }
        } elseif ($container->has($name)) {
            // EventAwareObject name => Array<int, Subscriber>
            $container->extend($name, function (EventAwareInterface $object) use ($listener, $container) {
                $object->subscribe($container->resolve($listener));

                return $object;
            });
        }
    }

    public function __get(string $name)
    {
        if ($name === 'config') {
            return $this->getContainer()->getParameters();
        }

        if ($name === 'container') {
            return $this->getContainer();
        }

        throw new OutOfRangeException('No such property: ' . $name . ' in ' . static::class);
    }

    /**
     * handleListeners
     *
     * @param  mixed      $listeners
     * @param  Container  $container
     *
     * @return  void
     */
    public function handleListeners(mixed $listeners, Container $container): void
    {
        foreach ($listeners as $name => $listener) {
            $this->handleListener($container, $this->getEventDispatcher(), $name, $listener);
        }
    }
}
