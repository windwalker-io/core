<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2017 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Router;

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
     * Property type.
     *
     * @var  string
     */
    protected $type = MainRouter::TYPE_PATH;

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
     * RouteString constructor.
     *
     * @param RouteBuilderInterface $router
     * @param string                $route
     * @param array                 $queries
     */
    public function __construct(RouteBuilderInterface $router, $route, $queries = [])
    {
        $this->route   = $route;
        $this->router  = $router;
        $this->queries = (array) $queries;
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
        $this->type = $type;

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
     * @return  RouteString
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
     * @return  RouteString
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
     * @return  RouteString
     */
    public function page($page)
    {
        return $this->addVar('page', $page);
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
     * @since  __DEPLOY_VERSION__
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
     * toString
     *
     * @return  string
     */
    public function toString()
    {
        if ($this->mute) {
            $uri = $this->router->generate($this->route, $this->queries, $this->type);
        } else {
            $uri = $this->router->route($this->route, $this->queries, $this->type);
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
}
