<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Router;

/**
 * The RouteBuilderTrait class.
 *
 * @since  3.0
 */
trait RouteBuilderTrait
{
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
	 * @param   string  $text
	 *
	 * @return  string
	 */
	public function escape($text)
	{
		return htmlspecialchars($text);
	}
}
