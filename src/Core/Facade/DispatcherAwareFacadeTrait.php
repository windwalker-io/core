<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Facade;

use Windwalker\Core\Event\DispatcherAwareStaticTrait;
use Windwalker\Event\Dispatcher;
use Windwalker\Core\Ioc;

/**
 * The DispatcherAwareStaticTrait class.
 * 
 * @since  2.0
 */
trait DispatcherAwareFacadeTrait
{
	use DispatcherAwareStaticTrait

	/**
	 * Method to get property Dispatcher
	 *
	 * @return  Dispatcher
	 */
	public static function getDispatcher()
	{
		if (!static::$dispatcher)
		{
			static::$dispatcher = Ioc::getDispatcher();
		}

		return static::$dispatcher;
	}
}
