<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2015 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Debugger\View\Events;

use Windwalker\Debugger\View\AbstractDebuggerHtmlView;

/**
 * The SystemHtmlView class.
 *
 * @since  2.1.1
 */
class EventsHtmlView extends AbstractDebuggerHtmlView
{
    /**
     * prepareData
     *
     * @param \Windwalker\Data\Data $data
     *
     * @return  void
     */
    protected function prepareData($data)
    {
        $data->collector = $data->item['collector'];

        $data->eventExecuted  = $data->collector['event.executed'];
        $data->eventListeners = $data->collector['event.listeners'];

        // Executed Events
        $events = [];

        foreach ((array) $data->eventExecuted as $event) {
            $eventName = $event['event'];

            if (!isset($events[$eventName])) {
                $events[$eventName] = [];
            }

            foreach ($event['listeners'] as $listener) {
                $id = implode('::', (array) $listener);

                if (!isset($events[$id])) {
                    $events[$eventName][$id] = [
                        'name' => $eventName,
                        'times' => 0,
                        'listener' => implode('::', (array) $listener),
                    ];
                }

                $events[$eventName][$id]['times']++;
            }
        }

        $data->executed = $events;

        // Event No executed
        $events = [];

        foreach ((array) $data->eventListeners as $eventName => $listeners) {
            foreach ($listeners as $listener) {
                if (!empty($data->executed[$eventName])) {
                    continue;
                }

                if (!isset($events[$eventName])) {
                    $events[$eventName] = [];
                }

                $events[$eventName][] = [
                    'name' => $eventName,
                    'times' => 0,
                    'listener' => implode('::', (array) $listener),
                ];
            }
        }

        $data->noExecuted = $events;
    }
}
