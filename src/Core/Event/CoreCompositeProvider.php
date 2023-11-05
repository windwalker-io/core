<?php

declare(strict_types=1);

namespace Windwalker\Core\Event;

use Windwalker\Event\Listener\ListenerPriority;
use Windwalker\Event\Provider\CompositeListenerProvider;

/**
 * The CoreCompositeProvider class.
 */
class CoreCompositeProvider extends CompositeListenerProvider
{
    protected EventCollector $collector;

    /**
     * @inheritDoc
     */
    public function on(
        string $event,
        callable $listener,
        ?int $priority = ListenerPriority::NORMAL
    ): void {
        parent::on($event, $listener, $priority);

        $this->collector->collectListener($event, $listener);
    }

    /**
     * @return EventCollector
     */
    public function getCollector(): EventCollector
    {
        return $this->collector;
    }

    /**
     * @param  EventCollector  $collector
     *
     * @return  static  Return self to support chaining.
     */
    public function setCollector(EventCollector $collector): static
    {
        $this->collector = $collector;

        return $this;
    }
}
