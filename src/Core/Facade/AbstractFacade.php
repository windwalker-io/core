<?php
/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2015 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Facade;

use Windwalker\DI\Container;
use Windwalker\Core\Ioc;

/**
 * The AbstractFacade class.
 *
 * @since  {DEPLOY_VERSION}
 */
abstract class AbstractFacade implements FacadeInterface
{
	/**
	 * getInstance
	 *
	 * @param bool $forceNew
	 *
	 * @return mixed
	 */
	public static function getInstance($forceNew = false)
	{
		return static::getContainer()->get(static::getDIKey(), $forceNew);
	}

	/**
	 * Method to get property Container
	 *
	 * @param  string  $child
	 *
	 * @return Container
	 */
	public static function getContainer($child = null)
	{
		return Ioc::factory($child ? : static::getContainerName());
	}

	/**
	 * Method to get property Child
	 *
	 * @return  string
	 */
	public static function getContainerName()
	{
		return null;
	}
}
