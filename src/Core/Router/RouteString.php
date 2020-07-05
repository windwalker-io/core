<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2017 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Router;

use Windwalker\Uri\PsrUri;
use Windwalker\Utilities\Arr;
use Windwalker\Utilities\Classes\StringableInterface;

/**
 * The RouteString class.
 *
 * @since  3.2
 */
class RouteString implements StringableInterface
{
    /**
     * Property route.
     *
     * @var  string
     */
    protected $route;

    /**
     * Property router.
     *
     * @var  RouteBuilderInterface
     */
    protected $router;

    /**
     * Property queries.
     *
     * @var  array
     */
    protected $queries = [];

    /**
     * Property escape.
     *
     * @var  bool
     */
    protected $escape = false;

    /**
     * Property mute.
     *
     * @var  bool
     */
    protected $mute = false;

    /**
     * @var array
     */
    protected $config;

    /**
     * RouteString constructor.
     *
     * @param RouteBuilderInterface $router
     * @param string                $route
     * @param array                 $queries
     * @param array                 $config
     */
    public function __construct(RouteBuilderInterface $router, $route, $queries = [], array $config = [])
    {
        $this->route   = $route;
        $this->router  = $router;
        $this->queries = (array) $queries;

        if (is_string($config)) {
            $config = [
                'type' => $config
            ];
        }

        $this->config = $config;
    }

    /**
     * type
     *
     * @param string $type
     *
     * @return  $this
     */
    public function type($type)
    {
        $this->config['type'] = $type;

        return $this;
    }

    /**
     * full
     *
     * @return  static
     */
    public function full()
    {
        return $this->type(MainRouter::TYPE_FULL);
    }

    /**
     * path
     *
     * @return  static
     */
    public function path()
    {
        return $this->type(MainRouter::TYPE_PATH);
    }

    /**
     * raw
     *
     * @return  static
     */
    public function raw()
    {
        return $this->type(MainRouter::TYPE_RAW);
    }

    /**
     * escape
     *
     * @param bool $bool
     *
     * @return  $this
     */
    public function escape($bool = true)
    {
        $this->escape = (bool) $bool;

        return $this;
    }

    /**
     * id
     *
     * @param int $id
     *
     * @return  static
     */
    public function id($id)
    {
        return $this->addVar('id', $id);
    }

    /**
     * alias
     *
     * @param string $alias
     *
     * @return  static
     */
    public function alias($alias)
    {
        return $this->addVar('alias', $alias);
    }

    /**
     * page
     *
     * @param int $page
     *
     * @return  static
     */
    public function page($page)
    {
        return $this->addVar('page', $page);
    }

    /**
     * layout
     *
     * @param string $layout
     *
     * @return  static
     *
     * @since  3.5.8
     */
    public function layout($layout)
    {
        return $this->addVar('layout', $layout);
    }

    /**
     * task
     *
     * @param string $task
     *
     * @return  RouteString
     *
     * @since  3.5.8
     */
    public function task($task)
    {
        return $this->addVar('task', $task);
    }

    /**
     * addVar
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return  $this
     */
    public function addVar($name, $value)
    {
        return $this->var($name, $value);
    }

    /**
     * addVar
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return  $this
     */
    public function var($name, $value)
    {
        $this->queries[$name] = $value;

        return $this;
    }

    /**
     * delVar
     *
     * @param string $name
     *
     * @return  static
     *
     * @since  3.5.5
     */
    public function delVar(string $name)
    {
        unset($this->queries[$name]);

        return $this;
    }

    /**
     * getVar
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return  mixed
     */
    public function getVar($name, $default = null)
    {
        return Arr::get($this->queries, $name, $default);
    }

    /**
     * Set config value.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return  $this
     *
     * @since  3.5.19
     */
    public function c(string $name, $value)
    {
        $this->config[$name] = $value;

        return $this;
    }

    /**
     * toString
     *
     * @return  string
     */
    public function toString()
    {
        $config = $this->config;

        if (is_string($config)) {
            $config = [
                'type' => $config
            ];
        }

        $config['type'] = $config['type'] ?? MainRouter::TYPE_PATH;

        if ($this->mute) {
            $uri = $this->router->generate($this->route, $this->queries, $config);
        } else {
            $uri = $this->router->route($this->route, $this->queries, $config);
        }

        if ($this->escape) {
            $uri = $this->router->escape($uri);
        }

        return $uri;
    }

    /**
     * Magic method to convert this object to string.
     *
     * @return  string
     */
    public function __toString()
    {
        try {
            return $this->toString();
        } catch (\Exception $e) {
            trigger_error((string) $e, E_USER_ERROR);

            return (string) $e;
        }
    }

    /**
     * Method to get property Queries
     *
     * @return  array
     */
    public function getQueries()
    {
        return $this->queries;
    }

    /**
     * Method to set property queries
     *
     * @param   array $queries
     *
     * @return  static  Return self to support chaining.
     */
    public function setQueries($queries)
    {
        $this->queries = $queries;

        return $this;
    }

    /**
     * Method to get property Route
     *
     * @return  string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Method to set property route
     *
     * @param   string $route
     *
     * @return  static  Return self to support chaining.
     */
    public function setRoute($route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Method to get property Router
     *
     * @return  RouteBuilderInterface
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * Method to set property router
     *
     * @param   RouteBuilderInterface $router
     *
     * @return  static  Return self to support chaining.
     */
    public function setRouter($router)
    {
        $this->router = $router;

        return $this;
    }

    /**
     * Method to get property Escape
     *
     * @return  bool
     */
    public function getEscape()
    {
        return $this->escape;
    }

    /**
     * Method to get property Mute
     *
     * @return  bool
     */
    public function getMute()
    {
        return $this->mute;
    }

    /**
     * Method to set property mute
     *
     * @param   bool $mute
     *
     * @return  static  Return self to support chaining.
     */
    public function mute($mute = true)
    {
        $this->mute = (bool) $mute;

        return $this;
    }

    /**
     * asUri
     *
     * @return  PsrUri
     *
     * @since  3.5.18.4
     */
    public function asUri(): PsrUri
    {
        return new PsrUri((string) $this);
    }
}
