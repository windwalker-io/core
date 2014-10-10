<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Event;

use Windwalker\Event\Dispatcher;
use Windwalker\Event\Event;

/**
 * DispatcherAwareStaticTrait
 *
 * @since  {DEPLOY_VERSION}
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