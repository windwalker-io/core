<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Router;

use Windwalker\Core\Facade\Facade;

/**
 * The Router class.
 *
 * @method static string build() build($route)
 * 
 * @since  {DEPLOY_VERSION}
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
