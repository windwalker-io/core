<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Router;

use Windwalker\Core\Facade\AbstractProxyFacade;

/**
 * The Router class.
 * 
 * @since  2.0.
 *
 * @method  static  string  build()      build($route, $queries = array(), $type = RestfulRouter::TYPE_RAW, $xhtml = false)
 * @method  static  string  buildHttp()  buildHttp($route, $queries = array(), $type = RestfulRouter::TYPE_RAW)
 * @method  static  string  http()       http($route, $queries = array(), $type = RestfulRouter::TYPE_RAW)
 * @method  static  string  buildHtml()  buildHtml($route, $queries = array(), $type = RestfulRouter::TYPE_RAW)
 * @method  static  string  html()       html($route, $queries = array(), $type = RestfulRouter::TYPE_RAW)
 *
 * @see \Windwalker\Router\Router
 * @see \Windwalker\Core\Router\RestfulRouter
 */
abstract class Router extends AbstractProxyFacade
{
	const TYPE_RAW = 'raw';
	const TYPE_PATH = 'path';
	const TYPE_FULL = 'full';

	/**
	 * Method to get property DiKey
	 *
	 * @return  string
	 */
	public static function getDIKey()
	{
		return 'system.router';
	}
}
