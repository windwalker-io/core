<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Event;

use Windwalker\Event\Dispatcher;
use Windwalker\Event\Event;

/**
 * DispatcherAwareStaticTrait
 *
 * @since  2.0
 */
trait DispatcherAwareStaticTrait
{
	/**
	 * Property dispatcher.
	 *
	 * @var Dispatcher
	 */
	protected static $dispatcher;

	/**
	 * triggerEvent
	 *
	 * @param string|Event $event
	 * @param array        $args
	 *
	 * @return  mixed
	 */
	public static function triggerEvent($event, $args = array())
	{
		static::getDispatcher()->triggerEvent($event, $args);
	}

	/**
	 * Method to get property Dispatcher
	 *
	 * @return  Dispatcher
	 */
	public static function getDispatcher()
	{
		return static::$dispatcher;
	}

	/**
	 * Method to set property dispatcher
	 *
	 * @param   mixed $dispatcher
	 *
	 * @return  void
	 */
	public static function setDispatcher($dispatcher)
	{
		static::$dispatcher = $dispatcher;
	}
}
