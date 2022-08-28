<?php

/**
 * Part of eva project.
 *
 * @copyright  Copyright (C) 2022 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Event;

use Psr\EventDispatcher\EventDispatcherInterface;
use Windwalker\DI\Attributes\Inject;
use Windwalker\Event\DispatcherAwareInterface;
use Windwalker\Event\EventEmitter;
use Windwalker\Event\EventInterface;

/**
 * Trait CoreEventAwareTrait
 */
trait CoreEventAwareTrait
{
    /**
     * Property dispatcher.
     *
     * @var  EventEmitter|null
     */
    #[Inject]
    protected ?EventEmitter $dispatcher = null;

    /**
     * Trigger an event.
     *
     * @template T
     *
     * @param  object|string|T  $event  The event object or name.
     * @param  array            $args   The arguments to set in event.
     *
     * @return  EventInterface|object|T  The event after being passed through all listeners.
     *
     * @since  2.0
     */
    public function emit(object|string $event, array $args = []): object
    {
        return $this->getEventDispatcher()->emit($event, $args);
    }

    /**
     * Add a subscriber object with multiple listener methods to this dispatcher.
     * If object is not EventSubscriberInterface, it will be registered to all events matching it's methods name.
     *
     * @param  object    $subscriber  The listener
     * @param  int|null  $priority    The listener priority.
     *
     * @return  static  This method is chainable.
     *
     * @since   2.0
     */
    public function subscribe(object $subscriber, ?int $priority = null): static
    {
        $this->getEventDispatcher()->subscribe($subscriber, $priority);

        return $this;
    }

    /**
     * Add single listener.
     *
     * @param  string    $event
     * @param  callable  $callable
     * @param  int       $priority
     *
     * @return  static
     *
     * @since   3.0
     */
    public function on(string $event, callable $callable, ?int $priority = null): static
    {
        $this->getEventDispatcher()->on($event, $callable, $priority);

        return $this;
    }

    /**
     * getDispatcher
     *
     * @return  EventEmitter
     */
    public function getEventDispatcher(): EventEmitter
    {
        return $this->dispatcher ??= new EventEmitter();
    }

    public function addEventDealer(EventDispatcherInterface|DispatcherAwareInterface $dispatcher): static
    {
        if ($dispatcher instanceof DispatcherAwareInterface) {
            $dispatcher = $dispatcher->getEventDispatcher();
        }

        $this->getEventDispatcher()->addDealer($dispatcher);

        return $this;
    }
}
