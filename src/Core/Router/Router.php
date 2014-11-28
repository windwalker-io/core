<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Router;

use Windwalker\Core\Facade\Facade;
use Windwalker\Core\Ioc;

/**
 * The Router class.
 * 
 * @since  {DEPLOY_VERSION}
 */
abstract class Router extends Facade
{
	const TYPE_RAW = 1;
	const TYPE_PATH = 2;
	const TYPE_FULL = 3;

	/**
	 * Property key.
	 *
	 * @var  string
	 */
	protected static $key = 'system.router';

	/**
	 * build
	 *
	 * @param string $route
	 * @param array  $queries
	 * @param int    $type
	 * @param bool   $xhtml
	 *
	 * @return  string
	 */
	public static function build($route, $queries = array(), $type = self::TYPE_RAW, $xhtml = false)
	{
		$uri = static::getInstance()->build($route, $queries);

		if ($type == static::TYPE_PATH)
		{
			$uri = Ioc::getApplication()->get('uri.base.path') . ltrim($uri, '/');
		}
		elseif ($type == static::TYPE_FULL)
		{
			$uri = Ioc::getApplication()->get('uri.base.full') . '/' . $uri;
		}

		if ($xhtml)
		{
			$uri = htmlspecialchars($uri);
		}

		return $uri;
	}

	/**
	 * buildHtml
	 *
	 * @param string $route
	 * @param array  $queries
	 * @param int    $type
	 *
	 * @return  string
	 */
	public static function buildHtml($route, $queries = array(), $type = self::TYPE_PATH)
	{
		return static::build($route, $queries, $type, true);
	}

	/**
	 * buildHttp
	 *
	 * @param string $route
	 * @param array  $queries
	 * @param int    $type
	 *
	 * @return  string
	 */
	public static function buildHttp($route, $queries = array(), $type = self::TYPE_PATH)
	{
		return static::build($route, $queries, $type, false);
	}
}
