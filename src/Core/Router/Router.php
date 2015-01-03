<?php
/**
 * Part of Windwalker project.
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
 * @since  2.0.
 *
 * @method  static  string  build()      build($route, $queries = array(), $type = RestfulRouter::TYPE_RAW, $xhtml = false)
 * @method  static  string  buildHttp()  buildHttp($route, $queries = array(), $type = RestfulRouter::TYPE_RAW)
 * @method  static  string  buildHtml()  buildHtml($route, $queries = array(), $type = RestfulRouter::TYPE_RAW)
 *
 * @see \Windwalker\Router\Router
 * @see \Windwalker\Core\Router\RestfulRouter
 */
abstract class Router extends Facade
{
	/**
	 * Property key.
	 *
	 * @var  string
	 */
	protected static $key = 'system.router';
}
