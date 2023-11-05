<?php

declare(strict_types=1);

namespace Windwalker\Core\Event;

use Psr\EventDispatcher\ListenerProviderInterface;
use Windwalker\Utilities\Reflection\ReflectionCallable;

/**
 * The EventCollector class.
 *
 * @level 3
 */
class EventCollector
{
    protected array $invokedListeners = [];

    protected array $uninvokedListeners = [];

    public function __construct(public bool $isEnabled = false)
    {
    }

    public function collectTriggered(object $event): void
    {
        if (!$this->isEnabled) {
            return;
        }

        $this->invokedListeners[$event::class] ??= [];
    }

    public function collectInvokedListener(object $event, callable $listener): void
    {
        if (!$this->isEnabled) {
            return;
        }

        $name = $this->getListenerName($listener);

        $this->invokedListeners[$event::class] ??= [];

        $this->invokedListeners[$event::class][$name] ??= 0;

        $this->invokedListeners[$event::class][$name]++;

        // Remove un-triggered listener
        unset($this->uninvokedListeners[$event::class][$name]);

        if (empty($this->uninvokedListeners[$event::class])) {
            unset($this->uninvokedListeners[$event::class]);
        }
    }

    public function collectListener(string $event, callable $listener): void
    {
        if (!$this->isEnabled) {
            return;
        }

        $name = $this->getListenerName($listener);

        $this->uninvokedListeners[$event] ??= [];

        $this->uninvokedListeners[$event][$name] ??= 0;
    }

    /**
     * @return array
     */
    public function getInvokedListeners(): array
    {
        return $this->invokedListeners;
    }

    /**
     * @param  callable  $listener
     *
     * @return  string
     */
    protected function getListenerName(callable $listener): string
    {
        $ref = (new ReflectionCallable($listener))->getReflector();

        if ($ref instanceof \ReflectionFunction) {
            $name = $ref->getName();
        } elseif ($ref instanceof \ReflectionMethod) {
            $name = $ref->getDeclaringClass()->getName() . '::' . $ref->getName() . '()';
        } else {
            $name = (string) $ref;
        }

        return $name;
    }

    /**
     * @return array
     */
    public function getUninvokedListeners(): array
    {
        return $this->uninvokedListeners;
    }
}
