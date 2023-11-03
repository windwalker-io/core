<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
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
     * @template T
     *
     * @param  class-string<T>  $id
     * @param  bool             $forceNew
     *
     * @return T
     */
    public function get(string $id, bool $forceNew = false): mixed;

    /**
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
     * @param  callable     $callable
     * @param  array        $args
     * @param  object|null  $context
     * @param  int          $options
     *
     * @return  mixed
     */
    public function call(callable $callable, array $args = [], ?object $context = null, int $options = 0): mixed;

    /**
     * @param  string  $id
     * @param  mixed   $value
     * @param  int     $options
     *
     * @return  static
     */
    public function bind(string $id, mixed $value, int $options = 0): mixed;

    /**
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
