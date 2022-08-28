<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Event;

use Psr\EventDispatcher\ListenerProviderInterface;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Event\EventEmitter;

/**
 * The EventDispatcherRegistry class.
 */
class EventDispatcherRegistry
{
    protected array $events = [];

    protected RootEventEmitter $rootDispatcher;

    /**
     * EventDispatcherRegistry constructor.
     *
     * @param  ApplicationInterface  $app
     */
    public function __construct(protected ApplicationInterface $app)
    {
        $this->rootDispatcher = new RootEventEmitter($this);
    }

    public function createDispatcher(ListenerProviderInterface $provider = null): EventEmitter
    {
        $dispatcher = new EventEmitter($provider);

        $dispatcher->addDealer($this->rootDispatcher);

        return $dispatcher;
    }

    public function collect(object $event): void
    {
        if (!$this->app->isDebug()) {
            return;
        }

        $this->events[] = $event;
    }

    /**
     * @return RootEventEmitter
     */
    public function getRootDispatcher(): RootEventEmitter
    {
        return $this->rootDispatcher;
    }
}
