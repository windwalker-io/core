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
	const TYPE_RAW = 'raw';
	const TYPE_PATH = 'path';
	const TYPE_FULL = 'full';
	
	/**
	 * build
	 *
	 * @param string $route
	 * @param array  $queries
	 * @param string $type
	 *
	 * @return string
	 */
	public function route($route, $queries = [], $type = MainRouter::TYPE_PATH);

	/**
	 * to
	 *
	 * @param string $route
	 * @param array  $queries
	 *
	 * @return  RouteString
	 */
	public function to($route, $queries = []);

	/**
	 * generate
	 *
	 * @param string  $route
	 * @param array   $queries
	 * @param string  $type
	 *
	 * @return  string
	 */
	public function generate($route, $queries = [], $type = MainRouter::TYPE_PATH);

	/**
	 * fullRoute
	 *
	 * @param string $route
	 * @param array  $queries
	 *
	 * @return  string
	 */
	public function fullRoute($route, $queries = []);

	/**
	 * rawRoute
	 *
	 * @param string $route
	 * @param array  $queries
	 *
	 * @return  string
	 */
	public function rawRoute($route, $queries = []);

	/**
	 * escape
	 *
	 * @param   string  $text
	 *
	 * @return  string
	 */
	public function escape($text);
}
