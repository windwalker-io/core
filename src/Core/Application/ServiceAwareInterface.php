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
     * @param  string|null      $tag
     *
     * @return T
     */
    public function retrieve(string $id, bool $forceNew = false, ?string $tag = null): mixed;

    /**
     * Is this service registered?
     *
     * @param  string                 $id
     * @param  \UnitEnum|string|null  $tag
     *
     * @return  bool
     */
    public function hasService(string $id, \UnitEnum|string|null $tag = null): bool;

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
     * @param  string|null      $tag
     *
     * @return  T
     */
    public function service(string $class, array $args = [], int $options = 0, ?string $tag = null): object;

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
     * @param  string       $id
     * @param  mixed        $value
     * @param  int          $options
     * @param  string|null  $tag
     *
     * @return  static
     */
    public function bind(string $id, mixed $value, int $options = 0, ?string $tag = null): mixed;

    /**
     * Resolve a definition of DI.
     *
     * @template T
     *
     * @param  mixed|class-string<T>  $source
     * @param  array                  $args
     * @param  int                    $options
     * @param  string|null            $tag
     *
     * @return mixed|T
     */
    public function resolve(mixed $source, array $args = [], int $options = 0, ?string $tag = null): mixed;
}
