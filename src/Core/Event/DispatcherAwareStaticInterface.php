<?php
/**
 * Part of starter project. 
 *
 * @copyright  Copyright (C) 2014 {ORGANIZATION}. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Event;

use Windwalker\Event\Event;

/**
 * The DispatcherAwareFacadeInterface class.
 * 
 * @since  {DEPLOY_VERSION}
 */
interface DispatcherAwareStaticInterface
{
	/**
	 * triggerEvent
	 *
	 * @param string|Event $event
	 * @param array        $args
	 *
	 * @return  mixed
	 */
	public static function triggerEvent($event, $args = array());
}
 