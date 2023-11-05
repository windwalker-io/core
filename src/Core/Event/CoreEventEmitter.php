<?php

declare(strict_types=1);

namespace Windwalker\Core\Event;

use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Windwalker\Event\EventEmitter;

/**
 * The RootEventEmitter class.
 */
class CoreEventEmitter extends EventEmitter
{
    /**
     * @inheritDoc
     */
    public function __construct(protected EventCollector $collector, ListenerProviderInterface $provider = null)
    {
        if (!$provider instanceof CoreCompositeProvider) {
            $provider = new CoreCompositeProvider($provider);
        }

        if ($provider instanceof CoreCompositeProvider) {
            $provider->setCollector($this->collector);
        }

        parent::__construct($provider);
    }

    /**
     * @return EventCollector
     */
    public function getCollector(): EventCollector
    {
        return $this->collector;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(object $event): object
    {
        $event = parent::dispatch($event);

        $this->collector->collectTriggered($event);

        return $event;
    }

    /**
     * Invoke listener.
     *
     * @param  object    $event
     * @param  callable  $listener
     *
     * @return  void
     */
    protected function invokeListener(object $event, callable $listener): void
    {
        parent::invokeListener($event, $listener);

        $this->collector->collectInvokedListener($event, $listener);
    }
}
