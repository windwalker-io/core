<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Debugger\Helper;

use Windwalker\Core\Proxy\AbstractFacade;
use Windwalker\Debugger\DebuggerPackage;
use Windwalker\Dom\HtmlElement;
use Windwalker\Ioc;
use Windwalker\Profiler\Point\Collector;
use Windwalker\Utilities\ArrayHelper;

/**
 * The DebuggerHelper class.
 *
 * @method  static  Collector  getInstance()
 * 
 * @since  2.1.1
 */
abstract class DebuggerHelper extends AbstractFacade
{
	/**
	 * Property _key.
	 *
	 * @var  string
	 */
	protected static $_key = 'system.collector';

	/**
	 * addData
	 *
	 * @param   string $key
	 * @param   mixed  $value
	 * @param   int    $depth
	 */
	public static function addCustomData($key, $value, $depth = 5)
	{
		try
		{
			$collector = static::getInstance();
		}
		catch (\UnexpectedValueException $e)
		{
			return;
		}

		$data = $collector['custom.data'];

		if ($data === null)
		{
			$data = array();
		}

		if (is_array($value) || is_object($value))
		{
			$value = new HtmlElement('pre', ArrayHelper::dump($value, $depth));
		}

		$data[$key] = (string) $value;

		$collector['custom.data'] = $data;
	}

	/**
	 * getQueries
	 *
	 * @return  array
	 */
	public static function getQueries()
	{
		$collector = static::getInstance();

		return $collector['database.queries'];
	}

	/**
	 * enableConsole
	 *
	 * @return  void
	 */
	public static function enableConsole()
	{
		if (!Ioc::exists('windwalker.debugger'))
		{
			return;
		}

		/** @var DebuggerPackage $package */
		$package = Ioc::get('windwalker.debugger');

		$package->enableConsole();
	}

	/**
	 * disableConsole
	 *
	 * @return  void
	 */
	public static function disableConsole()
	{
		if (!Ioc::exists('windwalker.debugger'))
		{
			return;
		}

		/** @var DebuggerPackage $package */
		$package = Ioc::get('windwalker.debugger');

		$package->disableConsole();
	}
}
