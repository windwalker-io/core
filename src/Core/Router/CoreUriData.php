<?php
/**
 * Part of windwalker  project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Router;

use Windwalker\Core\Facade\AbstractProxyFacade;
use Windwalker\Uri\UriData;

/**
 * The CoreUriData class.
 *
 * @see  UriData
 *
 * @method static string full()
 * @method static string current()
 * @method static string script($uri = null)
 * @method static string root($uri = null)
 * @method static string route()
 * @method static string host($uri = null)
 * @method static string path($uri = null)
 * @method static string scheme()
 *
 * @since  3.0.1
 */
class CoreUriData extends AbstractProxyFacade
{
	/**
	 * Property _key.
	 *
	 * @var  string
	 */
	protected static $_key = 'uri';
}
