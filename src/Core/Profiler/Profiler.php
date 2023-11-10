<?php

declare(strict_types=1);

namespace Windwalker\Core\Profiler;

use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

/**
 * The Profiler class.
 */
class Profiler implements \JsonSerializable
{
    protected ?Stopwatch $stopwatch;

    /**
     * @var array<ProfilerItem>
     */
    protected array $items = [];

    protected ?StopwatchEvent $currentEvent = null;

    public function __construct(protected string $name, Stopwatch $stopwatch = null)
    {
        $this->stopwatch = $stopwatch ?? new Stopwatch(true);
    }

    public function start(): StopwatchEvent
    {
        $event = $this->stopwatch->start($this->name);

        $this->currentEvent = $event;

        return $event;
    }

    public function stop(): StopwatchEvent
    {
        $event = $this->stopwatch->stop($this->name);

        $this->currentEvent = null;

        return $event;
    }

    public function mark(string $label, array $tags = []): ProfilerItem
    {
        if (!$this->currentEvent) {
            $this->start();
        }

        $event = $this->stopwatch->lap($this->name);

        $periods = $event->getPeriods();

        $this->items[$label] = $item = new ProfilerItem($label, $periods[array_key_last($periods)]);

        return $item->setTags($tags);
    }

    /**
     * @param  string|null  $tag
     *
     * @return  array<ProfilerItem>
     */
    public function getItems(?string $tag = null): array
    {
        return iterator_to_array($this->iterate($tag));
    }

    /**
     * @param  string|null  $tag
     *
     * @return  \Generator<ProfilerItem>
     */
    public function iterate(?string $tag = null): \Generator
    {
        foreach ($this->items as $item) {
            if ($tag && !$item->hasTag($tag)) {
                continue;
            }

            yield $item->getLabel() => $item;
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return StopwatchEvent|null
     */
    public function getCurrentEvent(): ?StopwatchEvent
    {
        return $this->currentEvent;
    }

    public function getStartTime(): int|float|null
    {
        return $this->currentEvent?->getStartTime();
    }

    public function getEndTime(): int|float|null
    {
        return $this->currentEvent?->getEndTime();
    }

    public function getMemory(): ?int
    {
        return $this->currentEvent?->getMemory();
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->getName(),
            'startTime' => $this->getStartTime(),
            'endTime' => $this->getEndTime(),
            'memory' => $this->getMemory(),
            'items' => array_values($this->getItems())
        ];
    }
}
