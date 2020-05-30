<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Router;

use Windwalker\DI\ClassMeta;
use Windwalker\Router\Route;
use Windwalker\Test\TestHelper;

/**
 * The RouteBuilderTrait class.
 *
 * @since  3.0
 */
trait RouteBuilderTrait
{
    /**
     * Property mute.
     *
     * @var bool
     */
    protected $mute;

    /**
     * build
     *
     * @param string $route
     * @param array  $queries
     * @param string $type
     *
     * @return string
     * @throws \OutOfRangeException
     */
    public function route($route, $queries = [], $type = MainRouter::TYPE_PATH)
    {
        return $this->build($route, $queries, $type);
    }

    /**
     * to
     *
     * @param string $route
     * @param array  $queries
     *
     * @return  RouteString
     */
    public function to($route, $queries = [])
    {
        return (new RouteString($this, $route, $queries))->mute($this->mute);
    }

    /**
     * generate
     *
     * @param string $route
     * @param array  $queries
     * @param string $type
     *
     * @return  string
     */
    public function generate($route, $queries = [], $type = MainRouter::TYPE_PATH)
    {
        try {
            return $this->route($route, $queries, $type);
        } catch (\OutOfRangeException $e) {
            if ($this->package->app->get('system.debug', false)) {
                return sprintf('javascript:alert(\'%s\')', htmlentities($e->getMessage(), ENT_QUOTES, 'UTF-8'));
            }

            return '#';
        }
    }

    /**
     * fullRoute
     *
     * @param string $route
     * @param array  $queries
     *
     * @return  string
     */
    public function fullRoute($route, $queries = [])
    {
        return $this->route($route, $queries, static::TYPE_FULL);
    }

    /**
     * rawRoute
     *
     * @param string $route
     * @param array  $queries
     *
     * @return  string
     */
    public function rawRoute($route, $queries = [])
    {
        return $this->route($route, $queries, static::TYPE_RAW);
    }

    /**
     * escape
     *
     * @param   string $text
     *
     * @return  string
     */
    public function escape($text)
    {
        return htmlspecialchars($text);
    }

    /**
     * Method to get property Mute
     *
     * @return  bool
     *
     * @since  3.5.5
     */
    public function getMute(): bool
    {
        return $this->mute;
    }

    /**
     * Method to set property mute
     *
     * @param bool $mute
     *
     * @return  static  Return self to support chaining.
     *
     * @since  3.5.5
     */
    public function mute(bool $mute)
    {
        $this->mute = $mute;

        return $this;
    }

    /**
     * hasMiddleware
     *
     * @param Route                   $route
     * @param object|string|ClassMeta $middleware
     *
     * @return  bool
     *
     * @since  __DEPLOY_VERSION__
     */
    public static function hasMiddleware(Route $route, $middleware): bool
    {
        $getClassName = static function ($instance) {
            if ($instance instanceof ClassMeta) {
                return (string) TestHelper::getValue($instance, 'class');
            }

            if ($instance instanceof \Closure) {
                return spl_object_hash($instance);
            }

            if (is_string($instance) || is_callable($instance)) {
                return $instance;
            }

            if (is_object($instance)) {
                return get_class($instance);
            }

            throw new \InvalidArgumentException('Middleware instance is wrong type.');
        };

        $middlewareClass = trim($getClassName($middleware), '\\');

        foreach ($route->getExtra('middlewares') as $item) {
            if (trim($getClassName($item), '\\') === $middlewareClass) {
                return true;
            }
        }

        return false;
    }
}
