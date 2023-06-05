<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
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
    protected EventEmitter $rootDispatcher;

    /**
     * EventDispatcherRegistry constructor.
     *
     * @param  ApplicationInterface  $app
     */
    public function __construct(protected ApplicationInterface $app)
    {
        $this->rootDispatcher = new EventEmitter();
    }

    public function createDispatcher(
        EventCollector $collector,
        ListenerProviderInterface $provider = null
    ): CoreEventEmitter {
        $dispatcher = new CoreEventEmitter($collector, $provider);

        $dispatcher->addDealer($this->rootDispatcher);

        return $dispatcher;
    }

    /**
     * @return EventEmitter
     */
    public function getRootDispatcher(): EventEmitter
    {
        return $this->rootDispatcher;
    }
}
