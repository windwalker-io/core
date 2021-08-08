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
use Windwalker\Event\EventEmitter;

/**
 * The CoreEventEmitter class.
 */
class CoreEventEmitter extends EventEmitter
{
    /**
     * @inheritDoc
     */
    public function __construct(protected EventDispatcherRegistry $registry, ListenerProviderInterface $provider = null)
    {
        parent::__construct($provider);
    }

    /**
     * @inheritDoc
     */
    public function dispatch(object $event): object
    {
        $this->registry->collect($event);

        return parent::dispatch($event);
    }

    /**
     * @return EventDispatcherRegistry
     */
    public function getRegistry(): EventDispatcherRegistry
    {
        return $this->registry;
    }
}
