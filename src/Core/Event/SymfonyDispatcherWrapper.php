<?php

declare(strict_types=1);

namespace Windwalker\Core\Event;

use Psr\EventDispatcher\EventDispatcherInterface as PsrEventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * The SymfonyDispatcherWrapper class.
 */
class SymfonyDispatcherWrapper implements EventDispatcherInterface
{
    /**
     * SymfonyDispatcherWrapper constructor.
     *
     * @param  PsrEventDispatcherInterface  $dispatcher
     */
    public function __construct(protected PsrEventDispatcherInterface $dispatcher)
    {
    }

    /**
     * @inheritDoc
     */
    public function dispatch(object $event, ?string $eventName = null): object
    {
        return $this->dispatcher->dispatch($event);
    }

    /**
     * @return PsrEventDispatcherInterface
     */
    public function getInnerDispatcher(): PsrEventDispatcherInterface
    {
        return $this->dispatcher;
    }
}
