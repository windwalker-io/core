<?php
/**
 * Part of phoenix project.
 *
 * @copyright  Copyright (C) 2018 ${ORGANIZATION}.
 * @license    __LICENSE__
 */

namespace Windwalker\Core\Browser;

use WhichBrowser\Parser;

/**
 * The BrowserDetect class.
 *
 * @since  __DEPLOY_VERSION__
 */
class WhichBrowserFactory
{
	/**
	 * Property instances.
	 *
	 * @var  Parser
	 */
	protected static $instance;

	/**
	 * getInstance
	 *
	 * @return Parser
	 */
	public static function getInstance()
	{
		if (!static::$instance)
		{
			if (function_exists('getallheaders'))
			{
				$userAgent = getallheaders();
			}
			elseif (isset($_SERVER['HTTP_USER_AGENT']))
			{
				$userAgent = $_SERVER['HTTP_USER_AGENT'];
			}
			else
			{
				$userAgent = '';
			}

			static::$instance = new Parser($userAgent);
		}

		return static::$instance;
	}
}
