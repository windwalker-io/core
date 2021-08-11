<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

/**
 * Interface ServiceAwareInterface
 */
interface ServiceAwareInterface
{
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
    public function make(string $class, array $args = [], int $options = 0): object;

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
     */
    public function service(string $class, array $args = [], int $options = 0): object;

    /**
     * call
     *
     * @param  callable     $callable
     * @param  array        $args
     * @param  object|null  $context
     * @param  int          $options
     *
     * @return  mixed
     */
    public function call(callable $callable, array $args = [], ?object $context = null, int $options = 0): mixed;

    /**
     * bind
     *
     * @param  string  $id
     * @param  mixed   $value
     * @param  int     $options
     *
     * @return  static
     */
    public function bind(string $id, mixed $value, int $options = 0): mixed;

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
     */
    public function resolve(mixed $source, array $args = [], int $options = 0): mixed;
}
