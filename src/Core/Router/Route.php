<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Router;

use Windwalker\Core\Facade\AbstractProxyFacade;

/**
 * The Route class.
 *
 * @see  Route
 *
 * @method  static  string  get($route, $queries = array(), $type = CoreRouter::TYPE_RAW)
 * @method  static  string  encode($route, $queries = array(), $type = CoreRouter::TYPE_RAW)
 *
 * @since  {DEPLOY_VERSION}
 */
class Route extends AbstractProxyFacade
{
	const TYPE_RAW = 'raw';
	const TYPE_PATH = 'path';
	const TYPE_FULL = 'full';

	/**
	 * Property _key.
	 *
	 * @var  string
	 */
	protected static $_key = 'route';
}
