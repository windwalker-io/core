<?php

declare(strict_types=1);

namespace Windwalker\Core\Profiler;

use Symfony\Component\Stopwatch\StopwatchEvent;
use Symfony\Component\Stopwatch\StopwatchPeriod;

/**
 * The ProfilerItem class.
 */
class ProfilerItem implements \JsonSerializable
{
    protected array $tags = [];

    public protected(set) int $memory = 0;
    public protected(set) int $memoryCurrent = 0;
    public protected(set) int $memoryPeak = 0;
    public protected(set) int $memoryPeakCurrent = 0;

    public function __construct(protected string $label, protected StopwatchPeriod $period)
    {
        $this->memory = memory_get_usage(true);
        $this->memoryCurrent = memory_get_usage(false);
        $this->memoryPeak = memory_get_peak_usage(true);
        $this->memoryPeakCurrent = memory_get_peak_usage(false);
    }

    public function getStartTime(): float|int
    {
        return $this->period->getStartTime();
    }

    public function getEndTime(): float|int
    {
        return $this->period->getEndTime();
    }

    public function getDuration(): float|int
    {
        return $this->period->getDuration();
    }

    public function getMemory(): int
    {
        return $this->memoryCurrent;
    }

    /**
     * @return StopwatchPeriod
     */
    public function getPeriod(): StopwatchPeriod
    {
        return $this->period;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param  array  $tags
     *
     * @return  static  Return self to support chaining.
     */
    public function setTags(array $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags, true);
    }

    public function jsonSerialize(): array
    {
        return [
            'label' => $this->getLabel(),
            'startTime' => $this->getStartTime(),
            'endTime' => $this->getEndTime(),
            'duration' => $this->getDuration(),
            'memory' => $this->getMemory(),
            'tags' => $this->getTags()
        ];
    }
}
