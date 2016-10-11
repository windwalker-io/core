<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Debugger\Profiler;

use Windwalker\Core\Facade\AbstractProxyFacade;

/**
 * The Profiler class.
 *
 * @see  \Windwalker\Profiler\Profiler
 *
 * @since  1.0
 */
class Profiler extends AbstractProxyFacade
{
	/**
	 * Property _key.
	 *
	 * @var  string
	 */
	protected static $_key = 'profiler';

	/**
	 * mark
	 *
	 * @param string  $name
	 * @param string  $context
	 * @param array   $data
	 *
	 * @return  void
	 */
	public static function mark($name, $context = null, $data = [])
	{
		if ($context)
		{
			$name .= ' / ' . $context;
		}

		$name .= sprintf('(%s)', uniqid());

		try
		{
			static::getInstance()->mark($name, $data);
		}
		catch (\Exception $e)
		{
			return;
		}
	}
}
