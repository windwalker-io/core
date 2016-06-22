<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Facade;

/**
 * The AbstractProxyFacade class.
 *
 * @since  2.1.1
 */
abstract class AbstractProxyFacade extends AbstractFacade
{
	/**
	 * Handle dynamic, static calls to the object.
	 *
	 * @param   string  $method  The method name.
	 * @param   array   $args    The arguments of method call.
	 *
	 * @return  mixed
	 */
	public static function __callStatic($method, $args)
	{
		$instance = static::getInstance();

		return $instance->$method(...$args);
	}
}
