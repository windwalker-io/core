<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Core\Event;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Windwalker\Core\Logger\LoggerAwareTrait;
use Windwalker\Event\Dispatcher;
use Windwalker\Event\DispatcherInterface;
use Windwalker\Event\EventInterface;
use Windwalker\Event\ListenerPriority;

/**
 * The CompositeDispatcher class.
 *
 * @since  2.1.1
 */
class CompositeDispatcher implements DispatcherInterface, LoggerAwareInterface, \ArrayAccess, \Countable, \IteratorAggregate
{
	use LoggerAwareTrait;
	
	/**
	 * Property dispatchers.
	 *
	 * @var  Dispatcher[]
	 */
	protected $dispatchers = [];

	/**
	 * Property log.
	 *
	 * @var  LoggerInterface
	 */
	protected $logger;

	/**
	 * CompositeDispatcher constructor.
	 *
	 * @param \Windwalker\Event\Dispatcher[] $dispatchers
	 */
	public function __construct(array $dispatchers)
	{
		$this->setDispatchers($dispatchers);
	}

	/**
	 * Trigger an event.
	 *
	 * @param   EventInterface|string $event The event object or name.
	 * @param   array                 $args  The arguments.
	 *
	 * @return  EventInterface  The event after being passed through all listeners.
	 */
	public function triggerEvent($event, $args = [])
	{
		foreach ($this->dispatchers as $dispatcher)
		{
			$event = $dispatcher->triggerEvent($event, $args);
		}

		return $event;
	}

	/**
	 * Add single listener.
	 *
	 * @param string   $event
	 * @param callable $callable
	 * @param int      $priority
	 *
	 * @return  static
	 */
	public function listen($event, $callable, $priority = ListenerPriority::NORMAL)
	{
		return $this->addListener($callable, [$event => $priority]);
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
	 *
	 * @since   2.0
	 */
	public function addListener($listener, $priorities = [])
	{
		foreach ($this->dispatchers as $dispatcher)
		{
			$dispatcher->addListener($listener, $priorities);
		}

		return $this;
	}

	/**
	 * triggerSubEvent
	 *
	 * @param   string                $name  The dispatcher name.
	 * @param   EventInterface|string $event The event object or name.
	 * @param   array                 $args  The arguments.
	 *
	 * @return  EventInterface
	 */
	public function triggerSubEvent($name, $event, $args = [])
	{
		return $this->getDispatcher($name)->triggerEvent($event, $args);
	}

	/**
	 * Add single listener.
	 *
	 * @param string   $name
	 * @param string   $event
	 * @param callable $callable
	 * @param int      $priority
	 *
	 * @return static
	 */
	public function subListen($name, $event, $callable, $priority = ListenerPriority::NORMAL)
	{
		return $this->addSubListener($name, $callable, [$event => $priority]);
	}

	/**
	 * addSubListener
	 *
	 * @param   string           $name        This dispatcher name.
	 * @param   object|\Closure  $listener    The listener
	 * @param   array|integer    $priorities  An associative array of event names as keys
	 *                                        and the corresponding listener priority as values.
	 *
	 * @return  $this
	 */
	public function addSubListener($name, $listener, $priorities = [])
	{
		$this->getDispatcher($name)->addListener($listener, $priorities);

		return $this;
	}

	/**
	 * addDispatcher
	 *
	 * @param   string              $name
	 * @param   DispatcherInterface $dispatcher
	 *
	 * @return  static
	 */
	public function addDispatcher($name, DispatcherInterface $dispatcher)
	{
		$name = strtolower($name);

		$dispatcher->name = $name;

		$this->dispatchers[$name] = $dispatcher;

		return $this;
	}

	/**
	 * getDispatcher
	 *
	 * @param   string  $name
	 *
	 * @return  Dispatcher
	 */
	public function getDispatcher($name)
	{
		$name = strtolower($name);

		if (!isset($this->dispatchers[$name]))
		{
			$this->addDispatcher($name, new Dispatcher);
		}

		return $this->dispatchers[$name];
	}

	/**
	 * removeDispatcher
	 *
	 * @param   string  $name
	 *
	 * @return  static
	 */
	public function removeDispatcher($name)
	{
		$name = strtolower($name);

		if (isset($this->dispatchers[$name]))
		{
			unset($this->dispatchers[$name]);
		}

		return $this;
	}

	/**
	 * hasDispatcher
	 *
	 * @param   string  $name
	 *
	 * @return  boolean
	 */
	public function hasDispatcher($name)
	{
		$name = strtolower($name);

		return isset($this->dispatchers[$name]);
	}

	/**
	 * Method to get property Dispatchers
	 *
	 * @return  \Windwalker\Event\Dispatcher[]
	 */
	public function getDispatchers()
	{
		return $this->dispatchers;
	}

	/**
	 * Method to set property dispatchers
	 *
	 * @param   \Windwalker\Event\Dispatcher[] $dispatchers
	 *
	 * @return  static  Return self to support chaining.
	 */
	public function setDispatchers(array $dispatchers)
	{
		foreach ($dispatchers as $name => $dispatcher)
		{
			$this->addDispatcher($name, $dispatcher);
		}

		return $this;
	}

	/**
	 * Retrieve an external iterator
	 *
	 * @return \Traversable An instance of an object implementing Iterator or Traversable
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->dispatchers);
	}

	/**
	 * Is a property exists or not.
	 *
	 * @param mixed $offset Offset key.
	 *
	 * @return  boolean
	 */
	public function offsetExists($offset)
	{
		return $this->hasDispatcher($offset);
	}

	/**
	 * Get a property.
	 *
	 * @param mixed $offset Offset key.
	 *
	 * @throws  \InvalidArgumentException
	 * @return  mixed The value to return.
	 */
	public function offsetGet($offset)
	{
		return $this->getDispatcher($offset);
	}

	/**
	 * Set a value to property.
	 *
	 * @param mixed $offset Offset key.
	 * @param mixed $value  The value to set.
	 *
	 * @throws  \InvalidArgumentException
	 * @return  void
	 */
	public function offsetSet($offset, $value)
	{
		$this->addDispatcher($offset, $value);
	}

	/**
	 * Unset a property.
	 *
	 * @param mixed $offset Offset key to unset.
	 *
	 * @throws  \InvalidArgumentException
	 * @return  void
	 */
	public function offsetUnset($offset)
	{
		$this->removeDispatcher($offset);
	}

	/**
	 * Count this object.
	 *
	 * @return  int
	 */
	public function count()
	{
		return count($this->dispatchers);
	}
}
