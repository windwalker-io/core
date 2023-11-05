<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Windwalker\DI\ContainerAwareTrait;
use Windwalker\DI\Exception;
use Windwalker\DI\Exception\DefinitionResolveException;

/**
 * Trait ServiceAwareTrait
 */
trait ServiceAwareTrait
{
    use ContainerAwareTrait;

    /**
     * @template T
     *
     * @param  class-string<T>  $id
     * @param  bool             $forceNew
     *
     * @return T
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function retrieve(string $id, bool $forceNew = false): mixed
    {
        return $this->getContainer()->get($id, $forceNew);
    }

    /**
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
     * @template T
     *
     * @param  class-string<T>  $class
     * @param  array            $args
     * @param  int              $options
     *
     * @return  T
     *
     * @throws ContainerExceptionInterface
     */
    public function service(string $class, array $args = [], int $options = 0): object
    {
        $container = $this->getContainer();

        if ($container->has($class)) {
            return $container->get($class);
        }

        return $container->createSharedObject($class, $args, $options);
    }

    /**
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
     * @param  string  $id
     * @param  mixed   $value
     * @param  int     $options
     *
     * @return  static
     *
     * @throws Exception\DefinitionException
     */
    public function bind(string $id, mixed $value, int $options = 0): mixed
    {
        $this->getContainer()->bind($id, $value, $options);

        return $this;
    }

    /**
     * @template T
     *
     * @param  mixed|class-string<T>  $source
     * @param  array                  $args
     * @param  int                    $options
     *
     * @return mixed|T
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function resolve(mixed $source, array $args = [], int $options = 0): mixed
    {
        return $this->getContainer()->resolve($source, $args, $options);
    }
}
