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
     * @param  string $class
     * @param  array  $args
     * @param  int    $options
     *
     * @return  object
     */
    public function make(string $class, array $args = [], int $options = 0): object;

    /**
     * service
     *
     * @param  mixed  $class
     * @param  array  $args
     * @param  int    $options
     *
     * @return  object
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
     * @param  mixed  $source
     * @param  array  $args
     * @param  int    $options
     *
     * @return  mixed
     */
    public function resolve(mixed $source, array $args = [], int $options = 0): mixed;
}
