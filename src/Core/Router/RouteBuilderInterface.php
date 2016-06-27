<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Router;

/**
 * Interface RouteBuilderInterface
 *
 * @since  {DEPLOY_VERSION}
 */
interface RouteBuilderInterface
{
	/**
	 * build
	 *
	 * @param string $route
	 * @param array  $queries
	 * @param string $type
	 *
	 * @return string
	 */
	public function route($route, $queries = array(), $type = CoreRouter::TYPE_PATH);

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
	 * secure
	 *
	 * @param string $route
	 * @param array  $queries
	 * @param string $type
	 *
	 * @return  string
	 */
	public function secure($route, $queries = array(), $type = CoreRouter::TYPE_PATH);
}
