<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later.
 */

namespace Windwalker\Core\Event;

use Windwalker\Event\Event;

/**
 * The DispatcherAwareFacadeInterface class.
 *
 * @since  2.0
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
    public static function triggerEvent($event, $args = []);
}
