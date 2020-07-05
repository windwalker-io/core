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
     * @param array  $config
     *
     * @return string
     */
    public function route($route, $queries = [], $config = [])
    {
        if (is_string($config)) {
            $config = [
                'type' => $config
            ];
        }

        $config['type'] = $config['type'] ?? MainRouter::TYPE_PATH;

        return $this->build($route, $queries, $config);
    }

    /**
     * to
     *
     * @param string $route
     * @param array  $queries
     * @param array  $config
     *
     * @return  RouteString
     */
    public function to($route, $queries = [], $config = [])
    {
        return (new RouteString($this, $route, $queries, $config))->mute($this->mute);
    }

    /**
     * generate
     *
     * @param string $route
     * @param array  $queries
     * @param array  $config
     *
     * @return  string
     */
    public function generate($route, $queries = [], $config = [])
    {
        try {
            return $this->route($route, $queries, $config);
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
     * @param array  $config
     *
     * @return  string
     */
    public function fullRoute($route, $queries = [], $config = [])
    {
        $config['type'] = static::TYPE_FULL;

        return $this->route($route, $queries, $config);
    }

    /**
     * rawRoute
     *
     * @param string $route
     * @param array  $queries
     * @param array  $config
     *
     * @return  string
     */
    public function rawRoute($route, $queries = [], $config = [])
    {
        $config['type'] = static::TYPE_RAW;

        return $this->route($route, $queries, $config);
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
     * @since  3.5.18.4
     */
    public static function hasMiddleware(Route $route, $middleware): bool
    {
        foreach ((array) $route->getExtra('middlewares') as $item) {
            if (ClassMeta::isSameClass($middleware, $item)) {
                return true;
            }
        }

        return false;
    }
}
