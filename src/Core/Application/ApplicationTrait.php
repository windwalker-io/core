<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

use Psr\EventDispatcher\EventDispatcherInterface;
use Windwalker\Attributes\AttributesAccessor;
use Windwalker\Core\Console\Process\ProcessRunnerTrait;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceAwareTrait;
use Windwalker\Event\Attributes\ListenTo;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Event\EventAwareTrait;
use Windwalker\Event\EventEmitter;

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

    protected function registerListeners(Container $container): void
    {
        $listeners = $this->config('listeners') ?? [];

        foreach ($listeners as $name => $listener) {
            $this->handleListener($container, $this->getEventDispatcher(), $name, $listener);
        }

        foreach (iterator_to_array($this->config) as $service => $config) {
            $listeners = $config['listeners'] ?? [];

            foreach ($listeners as $name => $listener) {
                $this->handleListener($container, $this->getEventDispatcher(), $name, $listener);
            }
        }
    }

    private function handleListener(
        Container $container,
        EventDispatcherInterface $dispatcher,
        string|int $name,
        mixed $listener
    ): void {
        if (is_numeric($name)) {
            if ($listener instanceof \Closure) {
                // Closure with ListenTo() attribute
                $event = AttributesAccessor::getFirstAttributeInstance($listener, ListenTo::class);
                $event->listen($dispatcher->getProvider(), $listener);
            } elseif ($dispatcher instanceof EventEmitter) {
                // Simply listener class name.
                $dispatcher->subscribe($container->resolve($listener));
            }
        } elseif (is_callable($listener)) {
            // Events name => listener
            $dispatcher->on($name, $listener);
        } elseif (is_array($listener)) {
            // EventAwareObject name => Array<EventName, Listener>
            $container->extend($name, function (EventAwareInterface $object) use ($listener, $container) {
                foreach ($listener as $eventName => $eventListener) {
                    $this->handleListener($container, $object->getEventDispatcher(), $eventName, $eventListener);
                }
                return $object;
            });
        } else {
            // Simply Subscriber class name
            $dispatcher->subscribe(
                $container->resolve($listener)
            );
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

        throw new \OutOfRangeException('No such property: ' . $name . ' in ' . static::class);
    }
}
