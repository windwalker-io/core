<?php

declare(strict_types=1);

namespace Windwalker\Core\Application;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Windwalker\DI\Definition\StoreDefinitionInterface;
use Windwalker\DI\DIOptions;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\DI\Exception\DefinitionNotFoundException;
use Windwalker\DI\Exception\DefinitionResolveException;
use Windwalker\DI\Exception\DependencyResolutionException;
use Windwalker\DI\PublicVisibleContainerAwareTrait;

/**
 * Trait ServiceAwareTrait
 */
trait ServiceAwareTrait
{
    use PublicVisibleContainerAwareTrait;

    /**
     * Get object from Container.
     *
     * @template T
     *
     * @param  class-string<T>        $id
     * @param  bool                   $forceNew
     * @param  \UnitEnum|string|null  $tag
     *
     * @return T
     * @throws DefinitionNotFoundException
     * @throws DependencyResolutionException
     */
    public function retrieve(string $id, bool $forceNew = false, \UnitEnum|string|null $tag = null): mixed
    {
        return $this->getContainer()->get($id, $forceNew, $tag);
    }

    public function hasService(string $id, \UnitEnum|string|null $tag = null): bool
    {
        return $this->getContainer()->has($id, tag: $tag);
    }

    /**
     * Create a single use object.
     *
     * @template T
     *
     * @param  class-string<T>  $class
     * @param  array            $args
     * @param  int|DIOptions    $options
     *
     * @return T
     * @throws DefinitionResolveException
     * @throws ReflectionException
     */
    public function make(string $class, array $args = [], int|DIOptions $options = new DIOptions()): object
    {
        return $this->getContainer()->newInstance($class, $args, $options);
    }

    /**
     * Get object or create if not exists, and save it as singleton.
     *
     * @template T
     *
     * @param  class-string<T>        $class
     * @param  array                  $args
     * @param  int|DIOptions          $options
     * @param  \UnitEnum|string|null  $tag
     *
     * @return T
     *
     * @throws DefinitionException
     * @throws DefinitionNotFoundException
     * @throws DependencyResolutionException
     */
    public function service(
        string $class,
        array $args = [],
        int|DIOptions $options = new DIOptions(),
        \UnitEnum|string|null $tag = null
    ): object {
        $container = $this->getContainer();

        if ($container->has($class, tag: $tag)) {
            return $container->get($class, tag: $tag);
        }

        return $container->createSharedObject($class, $args, $options, tag: $tag);
    }

    /**
     *  Call a function or method.
     *
     * @param  mixed          $callable
     * @param  array          $args
     * @param  object|null    $context
     * @param  int|DIOptions  $options
     *
     * @return  mixed
     *
     * @throws ContainerExceptionInterface
     * @throws DefinitionResolveException
     * @throws DependencyResolutionException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function call(
        mixed $callable,
        array $args = [],
        ?object $context = null,
        int|DIOptions $options = new DIOptions()
    ): mixed {
        return $this->getContainer()->call($callable, $args, $context, $options);
    }

    /**
     * Bind a value or object to Container.
     *
     * @param  string                 $id
     * @param  mixed                  $value
     * @param  int|DIOptions          $options
     * @param  \UnitEnum|string|null  $tag
     *
     * @return  StoreDefinitionInterface
     *
     * @throws DefinitionException
     */
    public function bind(
        string $id,
        mixed $value,
        int|DIOptions $options = new DIOptions(),
        \UnitEnum|string|null $tag = null
    ): StoreDefinitionInterface {
        return $this->getContainer()->bind($id, $value, $options, $tag);
    }

    /**
     * Resolve a definition of DI.
     *
     * @template T
     *
     * @param  mixed|class-string<T>  $source
     * @param  array                  $args
     * @param  int|DIOptions          $options
     * @param  \UnitEnum|string|null  $tag
     *
     * @return mixed|T
     * @throws DefinitionNotFoundException
     * @throws DefinitionResolveException
     * @throws DependencyResolutionException
     * @throws ReflectionException
     */
    public function resolve(
        mixed $source,
        array $args = [],
        int|DIOptions $options = new DIOptions(),
        \UnitEnum|string|null $tag = null
    ): mixed {
        return $this->getContainer()->resolve($source, $args, $options, $tag);
    }
}
