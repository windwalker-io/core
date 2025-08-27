<?php

declare(strict_types=1);

namespace Windwalker\Core\Application;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
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

    /**
     * Create a single use object.
     *
     * @template T
     *
     * @param  class-string<T>  $class
     * @param  array            $args
     * @param  int              $options
     *
     * @return  T
     * @throws DefinitionResolveException
     * @throws ReflectionException
     */
    public function make(string $class, array $args = [], int $options = 0): object
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
     * @param  int                    $options
     * @param  \UnitEnum|string|null  $tag
     *
     * @return  T
     *
     * @throws ContainerExceptionInterface
     * @throws DefinitionNotFoundException
     * @throws DependencyResolutionException
     * @throws DefinitionException
     * @throws NotFoundExceptionInterface
     */
    public function service(
        string $class,
        array $args = [],
        int $options = 0,
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
     * @param  mixed        $callable
     * @param  array        $args
     * @param  object|null  $context
     * @param  int          $options
     *
     * @return  mixed
     *
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     */
    public function call(mixed $callable, array $args = [], ?object $context = null, int $options = 0): mixed
    {
        return $this->getContainer()->call($callable, $args, $context, $options);
    }

    /**
     * Bind a value or object to Container.
     *
     * @param  string                 $id
     * @param  mixed                  $value
     * @param  int                    $options
     * @param  \UnitEnum|string|null  $tag
     *
     * @return  static
     *
     * @throws DefinitionException
     */
    public function bind(string $id, mixed $value, int $options = 0, \UnitEnum|string|null $tag = null): mixed
    {
        $this->getContainer()->bind($id, $value, $options, $tag);

        return $this;
    }

    /**
     * Resolve a definition of DI.
     *
     * @template T
     *
     * @param  mixed|class-string<T>  $source
     * @param  array                  $args
     * @param  int                    $options
     * @param  \UnitEnum|string|null  $tag
     *
     * @return mixed|T
     * @throws DefinitionNotFoundException
     * @throws DefinitionResolveException
     * @throws DependencyResolutionException
     * @throws ReflectionException
     */
    public function resolve(mixed $source, array $args = [], int $options = 0, \UnitEnum|string|null $tag = null): mixed
    {
        return $this->getContainer()->resolve($source, $args, $options, $tag);
    }
}
