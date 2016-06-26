<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

namespace Windwalker\Core\Event;

use Windwalker\Event\Dispatcher;
use Windwalker\Event\Event;
/**
 * The EventDispatcher class.
 * 
 * @since  2.1.1
 */
class EventDispatcher extends Dispatcher
{
	/**
	 * Property debug.
	 *
	 * @var  bool
	 */
	protected $debug = false;

	/**
	 * Property collector.
	 *
	 * @var  array
	 */
	protected $collector = array();

	/**
	 * Trigger an event.
	 *
	 * @param   Event|string $event The event object or name.
	 * @param   array                 $args  The arguments.
	 *
	 * @return  Event  The event after being passed through all listeners.
	 */
	public function triggerEvent($event, $args = array())
	{
		if (!is_string($event) && !$event instanceof Event)
		{
			throw new \InvalidArgumentException(sprintf(
				'%s::%s only allow Event object or string.', 
				get_called_class(), 
				__FUNCTION__
			));
		}

		if (!($event instanceof Event))
		{
			if (isset($this->events[$event]))
			{
				$event = $this->events[$event];
			}
			else
			{
				$event = new Event($event);
			}
		}

		$event->mergeArguments($args);

		$listeners = array();

		if (isset($this->listeners[$event->getName()]))
		{
			foreach ($this->listeners[$event->getName()] as $listener)
			{
				if ($event->isStopped())
				{
					return $event;
				}

				if (!is_callable($listener))
				{
					$listener = array($listener, $event->getName());
				}

				if (!is_callable($listener))
				{
					continue;
				}

				$listener($event);

				if ($this->debug)
				{
					$listeners[] = $listener;
				}
			}
		}

		if ($this->debug)
		{
			$executedListeners = array();

			foreach ($listeners as $listener)
			{
				if ($listener instanceof \Closure)
				{
					$listener = array('Closure');
				}

				if (is_object($listener[0]))
				{
					$listener[0] = get_class($listener[0]);
				}

				$executedListeners[] = $listener;
			}

			$this->collector[] = array(
				'event' => $event->getName(),
				'listeners' => $executedListeners
			);
		}

		return $event;
	}

	/**
	 * Add a listener to this dispatcher, only if not already registered to these events.
	 * If no events are specified, it will be registered to all events matching it's methods name.
	 * In the case of a closure, you must specify at least one event name.
	 *
	 * @param   object|\Closure $listener     The listener
	 * @param   array|integer   $priorities   An associative array of event names as keys
	 *                                        and the corresponding listener priority as values.
	 *
	 * @return  static  This method is chainable.
	 *
	 * @throws  \InvalidArgumentException
	 */
	public function addListener($listener, $priorities = array())
	{
		parent::addListener($listener, $priorities);

		return $this;
	}

	/**
	 * Method to get property Collector
	 *
	 * @return  array
	 */
	public function getCollector()
	{
		return $this->collector;
	}

	/**
	 * Method to set property collector
	 *
	 * @param   array  $collector
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setCollector($collector)
	{
		$this->collector = $collector;

		return $this;
	}

	/**
	 * Method to get property Debug
	 *
	 * @return  boolean
	 */
	public function getDebug()
	{
		return $this->debug;
	}

	/**
	 * Method to set property debug
	 *
	 * @param   boolean $debug
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setDebug($debug)
	{
		$this->debug = $debug;

		return $this;
	}
}
