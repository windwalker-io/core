<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Router;

use Windwalker\Core\Proxy\AbstractProxyFacade;
use Windwalker\Router\Route;

/**
 * The Router class.
 * 
 * @since  2.0.
 *
 * @method  static  string  build($route, $queries = array(), $type = CoreRouter::TYPE_RAW, $xhtml = false)
 * @method  static  string  buildHttp($route, $queries = array(), $type = CoreRouter::TYPE_RAW)
 * @method  static  string  http($route, $queries = array(), $type = CoreRouter::TYPE_RAW)
 * @method  static  string  buildHtml($route, $queries = array(), $type = CoreRouter::TYPE_RAW)
 * @method  static  string  html($route, $queries = array(), $type = CoreRouter::TYPE_RAW)
 * @method  static  Route   match($rawRoute, $method = 'GET', $options = array())
 * @method  static  Route   addRouteByConfig($name, $route, $package = null)
 * @method  static  CoreRouter  addRouteByConfigs($routes, $package = null)
 * @method  static  CoreRouter  addRoute($name, $pattern = null, $variables = array(), $method = array(), $options = array())
 * @method  static  CoreRouter  addRoutes(array $routes)
 * @method  static  Route          getRoute($name)
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
