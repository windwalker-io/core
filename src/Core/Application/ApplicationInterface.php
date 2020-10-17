<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Application;

use JetBrains\PhpStorm\ExitPoint;
use ReflectionException;
use Windwalker\DI\Exception\DefinitionException;
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\EventAwareInterface;

/**
 * Interface ApplicationInterface
 */
interface ApplicationInterface extends EventAwareInterface, DispatcherAwareInterface
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
     *
     * @throws DefinitionException
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
     *
     * @throws ReflectionException
     */
    public function call(callable $callable, array $args, ?object $context, int $options = 0): mixed;

    /**
     * bind
     *
     * @param  string  $id
     * @param  mixed   $value
     * @param  int     $options
     *
     * @return  static
     *
     * @throws DefinitionException
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
     *
     * @throws ReflectionException
     */
    public function resolve(mixed $source, array $args = [], int $options = 0): mixed;

    /**
     * Close this request.
     *
     * @param  mixed  $return
     *
     * @return  void
     */
    #[ExitPoint]
    public function close(mixed $return = ''): void;
}
