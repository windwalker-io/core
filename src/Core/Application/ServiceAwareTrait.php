<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

use ReflectionException;
use Windwalker\DI\ContainerAwareTrait;
use Windwalker\DI\Exception;

/**
 * Trait ServiceAwareTrait
 */
trait ServiceAwareTrait
{
    use ContainerAwareTrait;

    /**
     * make
     *
     * @template T
     *
     * @param  class-string<T>  $class
     * @param  array            $args
     * @param  int              $options
     *
     * @return  T
     */
    public function make($class, array $args = [], int $options = 0): object
    {
        return $this->getContainer()->newInstance($class, $args, $options);
    }

    /**
     * service
     *
     * @template T
     *
     * @param  class-string<T>  $class
     * @param  array            $args
     * @param  int              $options
     *
     * @return  T
     *
     * @throws Exception\DefinitionException
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
     * call
     *
     * @param  callable     $callable
     * @param  array        $args
     * @param  object|null  $context
     * @param  int          $options
     *
     * @return  mixed
     *
     * @throws ReflectionException
     */
    public function call(callable $callable, array $args = [], ?object $context = null, int $options = 0): mixed
    {
        return $this->getContainer()->call($callable, $args, $context, $options);
    }

    /**
     * bind
     *
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
     * resolve
     *
     * @template T
     *
     * @param  mixed|class-string<T>  $source
     * @param  array                  $args
     * @param  int                    $options
     *
     * @return mixed|T
     *
     * @throws ReflectionException
     */
    public function resolve(mixed $source, array $args = [], int $options = 0): mixed
    {
        return $this->getContainer()->resolve($source, $args, $options);
    }
}
