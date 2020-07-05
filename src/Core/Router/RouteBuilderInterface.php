<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Router;

/**
 * Interface RouteBuilderInterface
 *
 * @since  3.0
 */
interface RouteBuilderInterface
{
    public const TYPE_RAW = 'raw';

    public const TYPE_PATH = 'path';

    public const TYPE_FULL = 'full';

    /**
     * build
     *
     * @param string $route
     * @param array  $queries
     * @param array  $config
     *
     * @return string
     */
    public function route($route, $queries = [], $config = []);

    /**
     * to
     *
     * @param string $route
     * @param array  $queries
     * @param array  $config
     *
     * @return  RouteString
     */
    public function to($route, $queries = [], $config = []);

    /**
     * generate
     *
     * @param string $route
     * @param array  $queries
     * @param array  $config
     *
     * @return  string
     */
    public function generate($route, $queries = [], $config = []);

    /**
     * fullRoute
     *
     * @param string $route
     * @param array  $queries
     * @param array  $config
     *
     * @return  string
     */
    public function fullRoute($route, $queries = [], $config = []);

    /**
     * rawRoute
     *
     * @param string $route
     * @param array  $queries
     * @param array  $config
     *
     * @return  string
     */
    public function rawRoute($route, $queries = [], $config = []);

    /**
     * escape
     *
     * @param   string $text
     *
     * @return  string
     */
    public function escape($text);
}
