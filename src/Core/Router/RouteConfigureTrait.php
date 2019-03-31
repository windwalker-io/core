<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2019 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Router;

use Windwalker\DI\ClassMeta;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Classes\OptionAccessTrait;

/**
 * The RouteConfigureTrait class.
 *
 * @since  3.5
 */
trait RouteConfigureTrait
{
    use OptionAccessTrait;
    
    /**
     * methods
     *
     * @param string|array $methods
     *
     * @return  static
     *
     * @since  3.5
     */
    public function methods($methods)
    {
        $methods = (array) $methods;

        $this->options['method'] = $methods;

        return $this;
    }

    /**
     * actions
     *
     * @param array $actions
     *
     * @return  static
     *
     * @since  3.5
     */
    public function actions(array $actions)
    {
        $this->options['actions'] = $actions;

        return $this;
    }

    /**
     * action
     *
     * @param string $action
     * @param string $controller
     *
     * @return  static
     *
     * @since  3.5
     */
    public function action(string $action, string $controller)
    {
        $this->options['actions'][strtolower($action)] = $controller;

        return $this;
    }

    /**
     * allActions
     *
     * @param string $controller
     *
     * @return  static
     *
     * @since  3.5
     */
    public function allActions(string $controller)
    {
        $this->options['actions']['*'] = $controller;

        return $this;
    }

    /**
     * Save actions: post, patch, put
     *
     * @param string $controller
     *
     * @return  static
     *
     * @since  3.5
     */
    public function saveAction(string $controller)
    {
        $this->postAction($controller);
        $this->putAction($controller);
        $this->patchAction($controller);

        return $this;
    }

    /**
     * package
     *
     * @param string $value
     *
     * @return  $this
     *
     * @since  3.5
     */
    public function package(string $value)
    {
        $this->options['package'] = $value;

        return $this;
    }

    /**
     * variables
     *
     * @param array $variables
     *
     * @return  static
     *
     * @since  3.5
     */
    public function variables(array $variables)
    {
        $this->options['variables'] = Arr::mergeRecursive(
            $this->options['variables'] ?? [],
            $variables
        );

        return $this;
    }

    /**
     * var
     *
     * @param string       $name
     * @param string|array $value
     *
     * @return  static
     *
     * @since  3.5
     */
    public function var(string $name, $value)
    {
        $this->options['variables'][$name] = $value;

        return $this;
    }

    /**
     * requirements
     *
     * @param array $requirements
     *
     * @return  static
     *
     * @since  3.5
     */
    public function requirements(array $requirements)
    {
        $this->options['requirements'] = Arr::mergeRecursive(
            $this->options['requirements'] ?? [],
            $requirements
        );

        return $this;
    }

    /**
     * requirement
     *
     * @param string $name
     * @param string $value
     *
     * @return  static
     *
     * @since  3.5
     */
    public function requirement(string $name, string $value)
    {
        $this->options['requirements'][$name] = $value;

        return $this;
    }

    /**
     * matchHook
     *
     * @param array $hooks
     *
     * @return  static
     *
     * @since  3.5
     */
    public function hooks(array $hooks)
    {
        $this->options['hooks'] = $hooks;

        return $this;
    }

    /**
     * matchHook
     *
     * @param callable $callable
     *
     * @return  static
     *
     * @since  3.5
     */
    public function matchHook(callable $callable)
    {
        $this->options['hooks']['match'] = $callable;

        return $this;
    }

    /**
     * buildHook
     *
     * @param callable $callable
     *
     * @return  static
     *
     * @since  3.5
     */
    public function buildHook(callable $callable)
    {
        $this->options['hooks']['build'] = $callable;

        return $this;
    }

    /**
     * middlewares
     *
     * @param array $middlewares
     *
     * @return  static
     *
     * @since  3.5
     */
    public function middlewares(array $middlewares)
    {
        foreach ($middlewares as $middleware) {
            $this->middleware($middleware);
        }

        return $this;
    }

    /**
     * middleware
     *
     * @param string|array|callable|ClassMeta $class
     *
     * @return  static
     *
     * @since  3.5
     */
    public function middleware($class)
    {
        $this->pushOption('middlewares', $class);

        return $this;
    }

    /**
     * port
     *
     * @param string $value
     *
     * @return  static
     *
     * @since  3.5
     */
    public function port(string $value)
    {
        $this->options['port'] = $value;

        return $this;
    }

    /**
     * sslPort
     *
     * @param string $value
     *
     * @return  static
     *
     * @since  3.5
     */
    public function sslPort(string $value)
    {
        $this->options['sslPort'] = $value;

        return $this;
    }

    /**
     * scheme
     *
     * @param string $value
     *
     * @return  static
     *
     * @since  3.5
     */
    public function scheme(string $value)
    {
        $this->options['scheme'] = $value;

        return $this;
    }

    /**
     * extra
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return  static
     *
     * @since  3.5
     */
    public function extra(string $name, $value)
    {
        $this->options['extra'] = Arr::set($this->options['extra'] ?? [], $name, $value);

        return $this;
    }

    /**
     * extraValues
     *
     * @param array $values
     *
     * @return  static
     *
     * @since  3.5
     */
    public function extraValues(array $values)
    {
        $this->options['extra'] = Arr::mergeRecursive(
            $this->options['extra'] ?? [],
            $values
        );

        return $this;
    }

    /**
     * clear
     *
     * @param string $name
     *
     * @return  self
     *
     * @since  3.5
     */
    public function clear(string $name): self
    {
        unset($this->options[$name]);

        return $this;
    }

    /**
     * __call
     *
     * @param string  $name
     * @param array   $args
     *
     * @return  mixed
     *
     * @since  3.5
     */
    public function __call($name, array $args = [])
    {
        if (strtolower(substr($name, -6)) === 'action') {
            return $this->action(substr($name, 0, -6), ...$args);
        }

        throw new \BadMethodCallException(sprintf('Method: %s not exists', $name));
    }
}
