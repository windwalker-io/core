<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2019 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Router;

use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Classes\OptionAccessTrait;

/**
 * The RouteData class.
 *
 * @method $this getAction(string $controller)
 * @method $this postAction(string $controller)
 * @method $this putAction(string $controller)
 * @method $this patchAction(string $controller)
 * @method $this deleteAction(string $controller)
 * @method $this headAction(string $controller)
 * @method $this optionsAction(string $controller)
 *
 * @since  __DEPLOY_VERSION__
 */
class RouteData
{
    use OptionAccessTrait;

    /**
     * Property name.
     *
     * @var string
     */
    protected $name;

    /**
     * Property groups.
     *
     * @var array
     */
    protected $groups = [];

    /**
     * RouteData constructor.
     *
     * @param string $name
     * @param array  $options
     */
    public function __construct(string $name = null, array $options = [])
    {
        $this->name    = $name;
        $this->options = $options;
    }

    /**
     * name
     *
     * @param string $name
     *
     * @return  static
     *
     * @since  __DEPLOY_VERSION__
     */
    public function name($name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * pattern
     *
     * @param string $value
     *
     * @return  static
     *
     * @since  __DEPLOY_VERSION__
     */
    public function pattern(string $value): self
    {
        $this->options['pattern'] = $value;

        return $this;
    }

    /**
     * pattern
     *
     * @param string $value
     *
     * @return  static
     *
     * @since  __DEPLOY_VERSION__
     */
    public function controller(string $value): self
    {
        $this->options['controller'] = $value;

        return $this;
    }

    /**
     * methods
     *
     * @param string|array $methods
     *
     * @return  static
     *
     * @since  __DEPLOY_VERSION__
     */
    public function methods($methods): self
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
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function actions(array $actions): self
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
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function action(string $action, string $controller): self
    {
        $this->options['actions'][strtolower($action)] = $controller;

        return $this;
    }

    /**
     * allActions
     *
     * @param string $controller
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function allActions(string $controller): self
    {
        $this->options['actions']['*'] = $controller;

        return $this;
    }

    /**
     * package
     *
     * @param string $value
     *
     * @return  $this
     *
     * @since  __DEPLOY_VERSION__
     */
    public function package(string $value): self
    {
        $this->options['package'] = $value;

        return $this;
    }

    /**
     * variables
     *
     * @param array $variables
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function variables(array $variables): self
    {
        Arr::mergeRecursive(
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
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function var(string $name, $value): self
    {
        $this->options['variables'][$name] = $value;

        return $this;
    }

    /**
     * requirements
     *
     * @param array $requirements
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function requirements(array $requirements): self
    {
        Arr::mergeRecursive(
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
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function requirement(string $name, string $value): self
    {
        $this->options['requirements'][$name] = $value;

        return $this;
    }

    /**
     * matchHook
     *
     * @param array $hooks
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function hooks(array $hooks): self
    {
        $this->options['hooks'] = $hooks;

        return $this;
    }

    /**
     * matchHook
     *
     * @param callable $callable
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function matchHook(callable $callable): self
    {
        $this->options['hooks']['match'] = $callable;

        return $this;
    }

    /**
     * buildHook
     *
     * @param callable $callable
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function buildHook(callable $callable): self
    {
        $this->options['hooks']['build'] = $callable;

        return $this;
    }

    /**
     * middlewares
     *
     * @param array $middlewares
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function middlewares(array $middlewares): self
    {
        foreach ($middlewares as $middleware) {
            $this->middleware($middleware);
        }

        return $this;
    }

    /**
     * middleware
     *
     * @param string $class
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function middleware(string $class): self
    {
        $this->options['middlewares'][] = $class;

        return $this;
    }

    /**
     * port
     *
     * @param string $value
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function port(string $value): self
    {
        $this->options['port'] = $value;

        return $this;
    }

    /**
     * sslPort
     *
     * @param string $value
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function sslPort(string $value): self
    {
        $this->options['sslPort'] = $value;

        return $this;
    }

    /**
     * scheme
     *
     * @param string $value
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function scheme(string $value): self
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
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function extra(string $name, $value): self
    {
        $this->options['extra'][$name] = $value;

        return $this;
    }

    /**
     * extraValues
     *
     * @param array $values
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function extraValues(array $values): self
    {
        $this->options['extra'] = Arr::mergeRecursive(
            $this->options['extra'] ?? [],
            $values
        );

        return $this;
    }

    /**
     * group
     *
     * @param string $group
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function group(string $group): self
    {
        $this->groups[] = $group;

        return $this;
    }

    /**
     * groups
     *
     * @param array $groups
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function groups(array $groups): self
    {
        $this->groups = $groups;

        return $this;
    }

    /**
     * getGroups
     *
     * @return  array
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * clear
     *
     * @param string $name
     *
     * @return  self
     *
     * @since  __DEPLOY_VERSION__
     */
    public function clear(string $name): self
    {
        unset($this->options[$name]);

        return $this;
    }

    /**
     * Method to get property Name
     *
     * @return  string
     *
     * @since  __DEPLOY_VERSION__
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * __call
     *
     * @param string  $name
     * @param array   $args
     *
     * @return  RouteData
     *
     * @since  __DEPLOY_VERSION__
     */
    public function __call($name, array $args = [])
    {
        if (strtolower(substr($name, -6)) === 'action') {
            return $this->action(substr($name, 0, -6), ...$args);
        }

        throw new \BadMethodCallException(sprintf('Method: %s not exists', $name));
    }
}
