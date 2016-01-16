<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Router;

use Windwalker\Core\Facade\AbstractProxyFacade;

/**
 * The Router class.
 * 
 * @since  2.0.
 *
 * @method  static  string  build($route, $queries = array(), $type = RestfulRouter::TYPE_RAW, $xhtml = false)
 * @method  static  string  buildHttp($route, $queries = array(), $type = RestfulRouter::TYPE_RAW)
 * @method  static  string  http($route, $queries = array(), $type = RestfulRouter::TYPE_RAW)
 * @method  static  string  buildHtml($route, $queries = array(), $type = RestfulRouter::TYPE_RAW)
 * @method  static  string  html($route, $queries = array(), $type = RestfulRouter::TYPE_RAW)
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
	 * Property _key.
	 *
	 * @var  string
	 */
	protected static $_key = 'system.router';
}
