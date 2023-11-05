<?php

declare(strict_types=1);

namespace Windwalker\Core\Application;

/**
 * Interface ServiceAwareInterface
 */
interface ServiceAwareInterface
{
    /**
     * Get object from Container.
     *
     * @template T
     *
     * @param  class-string<T>  $id
     * @param  bool             $forceNew
     *
     * @return T
     */
    public function retrieve(string $id, bool $forceNew = false): mixed;

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
     */
    public function make(string $class, array $args = [], int $options = 0): object;

    /**
     * Get object or create if not exists, and save it as singleton.
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
     * Call a function or method.
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
     * Bind a value or object to Container.
     *
     * @param  string  $id
     * @param  mixed   $value
     * @param  int     $options
     *
     * @return  static
     */
    public function bind(string $id, mixed $value, int $options = 0): mixed;

    /**
     * Resolve a definition of DI.
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
