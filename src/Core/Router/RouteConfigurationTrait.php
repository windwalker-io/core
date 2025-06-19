<?php

declare(strict_types=1);

namespace Windwalker\Core\Router;

use BadMethodCallException;
use Windwalker\DI\Definition\DefinitionInterface;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Classes\ChainingTrait;
use Windwalker\Utilities\Options\OptionAccessTrait;
use Windwalker\Utilities\Str;

use function Windwalker\DI\create;

trait RouteConfigurationTrait
{
    use ChainingTrait;
    use OptionAccessTrait;

    /**
     * methods
     *
     * @param  string|array  $methods
     *
     * @return  static
     *
     * @since  3.5
     */
    public function methods(string|array $methods): static
    {
        $methods = (array) $methods;

        $this->options['method'] = $methods;

        return $this;
    }

    public function getMethods(): array
    {
        return $this->options['method'] ?? [];
    }

    /**
     * handlers
     *
     * @param  array  $handlers
     *
     * @return  static
     *
     * @since  3.5
     */
    public function setHandlers(array $handlers): static
    {
        $handlers = array_change_key_case($handlers, CASE_LOWER);

        $this->options['handlers'] = $handlers;

        return $this;
    }

    /**
     * action
     *
     * @param  string|array                $methods
     * @param  callable|array|string|null  $handler
     * @param  string|null                 $task
     *
     * @return  static
     *
     * @since  3.5
     */
    public function handlers(string|array $methods, callable|array|string|null $handler, ?string $task = null): static
    {
        if ($task !== null) {
            $handler = [$handler, $task];
        }

        $methods = (array) $methods;

        foreach ($methods as $method) {
            $this->options['handlers'][strtolower($method)] = $handler;
        }

        return $this;
    }

    /**
     * allhandlers
     *
     * @param  callable|array|string|null  $handler
     * @param  string|null                 $task
     *
     * @return  static
     *
     * @since  3.5
     */
    public function allHandlers(callable|array|string|null $handler, ?string $task = null): static
    {
        $this->handlers('*', $handler, $task);

        return $this;
    }

    /**
     * Save handlers: post, patch, put
     *
     * @param  callable|array|string|null  $handler
     * @param  string|null                 $task
     *
     * @return  static
     *
     * @since  3.5
     */
    public function saveHandler(callable|array|string|null $handler, ?string $task = null): static
    {
        $this->postHandler($handler, $task);
        $this->putHandler($handler, $task);
        $this->patchHandler($handler, $task);

        return $this;
    }

    public function getHandlers(): array
    {
        return $this->options['handlers'] ?? [];
    }

    /**
     * variables
     *
     * @param  array  $variables
     *
     * @return  static
     *
     * @since  3.5
     */
    public function vars(array $variables): static
    {
        $this->options['vars'] = Arr::mergeRecursive(
            $this->options['vars'] ?? [],
            $variables
        );

        return $this;
    }

    /**
     * var
     *
     * @param  string        $name
     * @param  string|array  $value
     *
     * @return  static
     *
     * @since  3.5
     */
    public function var(string $name, mixed $value): static
    {
        $this->options['vars'][$name] = $value;

        return $this;
    }

    public function getVars(): array
    {
        return $this->options['vars'] ?? [];
    }

    /**
     * layoutPaths
     *
     * @param  array<string>  $paths
     *
     * @return  static
     */
    public function layoutPaths(string ...$paths): static
    {
        $this->options['layoutPaths'] ??= [];

        foreach ($paths as $path) {
            $this->options['layoutPaths'][] = $path;
        }

        return $this;
    }

    /**
     * matchHook
     *
     * @param  callable[]  $hooks
     *
     * @return  static
     *
     * @since  3.5
     */
    public function hooks(array $hooks): static
    {
        $this->options['hooks'] = $hooks;

        return $this;
    }

    /**
     * matchHook
     *
     * @param  callable  $callable
     *
     * @return  static
     *
     * @since  3.5
     */
    public function matchHook(callable $callable): static
    {
        $this->options['hooks']['match'] = $callable;

        return $this;
    }

    /**
     * buildHook
     *
     * @param  callable  $callable
     *
     * @return  static
     *
     * @since  3.5
     */
    public function buildHook(callable $callable): static
    {
        $this->options['hooks']['build'] = $callable;

        return $this;
    }

    public function getHooks(): array
    {
        return $this->options['hooks'] ?? [];
    }

    /**
     * middlewares
     *
     * @param  array  $middlewares
     *
     * @return  static
     *
     * @since  3.5
     */
    public function middlewares(array $middlewares): static
    {
        foreach ($middlewares as $middleware) {
            $this->middleware($middleware);
        }

        return $this;
    }

    /**
     * middleware
     *
     * @param  string|array|callable|DefinitionInterface  $class
     * @param  mixed                                      ...$args
     *
     * @return  static
     *
     * @since  3.5
     */
    public function middleware(mixed $class, ...$args): static
    {
        if (!$class) {
            return $this;
        }

        if (is_string($class)) {
            $class = create($class, ...$args);
        }

        $this->options['middlewares'] ??= [];
        $this->options['middlewares'][] = $class;

        return $this;
    }

    public function getMiddlewares(): array
    {
        return $this->options['middlewares'] ?? [];
    }

    /**
     * Subscribe event listeners.
     *
     * Arguments can be:
     * - (callable with #[ListenTo(EventName::class)])
     * - (EventName::class, callable)
     * - (EventAware::class, Subscriber::class)
     * - (EventAware::class, EventName::class, callable)
     *
     * @param ...$args
     *
     * @return  $this
     */
    public function subscribe(...$args): static
    {
        $this->options['subscribers'] ??= [];

        $count = count($args);

        if ($count === 1) {
            $this->options['subscribers'][] = $args[0];
        } elseif ($count === 2) {
            $this->options['subscribers'][$args[0]][] = $args[1];
        } elseif ($count === 3) {
            $this->options['subscribers'][$args[0]][$args[1]] = $args[2];
        } else {
            throw new BadMethodCallException(
                sprintf(
                    '%s() should got 1 ~ 3 arguments, %s given.',
                    __METHOD__,
                    $count
                )
            );
        }

        return $this;
    }

    public function subscribes(array $subscribers): static
    {
        $this->options['subscribers'] ??= [];

        $this->options['subscribers'] = Arr::mergeRecursive($this->options['subscribers'], $subscribers);

        return $this;
    }

    public function getSubscribers(): array
    {
        return $this->options['subscribers'] ?? [];
    }

    /**
     * scheme
     *
     * @param  string  $value
     *
     * @return  static
     *
     * @since  3.5
     */
    public function scheme(string $value): static
    {
        $this->options['scheme'] = $value;

        return $this;
    }

    public function getScheme(): ?string
    {
        return $this->options['scheme'] ?? null;
    }

    public function host(string $value): static
    {
        $this->options['hosts'][] = $value;

        return $this;
    }

    public function hosts(string|array ...$hosts): static
    {
        $hosts = Arr::collapse($hosts);

        $this->options['hosts'] = array_merge(
            $this->options['hosts'] ?? [],
            array_values($hosts)
        );

        return $this;
    }

    public function getHosts(): array
    {
        return $this->options['hosts'] ?? [];
    }

    public function getNamespace(): string
    {
        return $this->getExtraValue('namespace') ?? '';
    }

    /**
     * extra
     *
     * @param  string  $name
     * @param  mixed   $value
     *
     * @return  static
     *
     * @since  3.5
     */
    public function extra(string $name, mixed $value): static
    {
        $this->options['extra'] = Arr::set($this->options['extra'] ?? [], $name, $value);

        return $this;
    }

    /**
     * extraValues
     *
     * @param  array  $values
     *
     * @return  static
     *
     * @since  3.5
     */
    public function extraValues(array $values): static
    {
        $this->options['extra'] = Arr::mergeRecursive(
            $this->options['extra'] ?? [],
            $values
        );

        return $this;
    }

    public function getExtra(): array
    {
        return $this->options['extra'] ?? [];
    }

    public function getExtraValue(string $name): mixed
    {
        return $this->getExtra()[$name] ?? null;
    }

    /**
     * clear
     *
     * @param  string  $name
     *
     * @return  self
     *
     * @since  3.5
     */
    public function clear(string $name): self
    {
        unset($this->options[$name]);

        return $this;
    }

    /**
     * @param  string  $name
     * @param  array   $args
     *
     * @return  mixed
     *
     * @since  3.5
     */
    public function __call(string $name, array $args = [])
    {
        if (Str::endsWith(strtolower($name), 'handler')) {
            return $this->handlers(Str::removeRight(strtolower($name), 'handler'), ...$args);
        }

        throw new BadMethodCallException(sprintf('Method: %s not exists', $name));
    }
}
