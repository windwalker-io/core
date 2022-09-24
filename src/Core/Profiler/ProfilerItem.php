<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2022 __ORGANIZATION__.
 * @license    __LICENSE__
 */

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

    public function __construct(protected string $label, protected StopwatchPeriod $period)
    {
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
        return $this->period->getMemory();
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
