<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
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

    /**
     * EventDispatcherRegistry constructor.
     *
     * @param  ApplicationInterface  $app
     */
    public function __construct(protected ApplicationInterface $app)
    {
    }

    public function createDispatcher(ListenerProviderInterface $provider = null): EventEmitter
    {
        return new CoreEventEmitter($provider);
    }

    public function collect(object $event): void
    {
        if (!$this->app->isDebug()) {
            return;
        }

        $this->events[] = $event;
    }
}
