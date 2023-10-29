<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2022 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Profiler;

use Symfony\Component\Stopwatch\Stopwatch;

/**
 * The ProfilerFactory class.
 */
class ProfilerFactory
{
    /**
     * @var array<Profiler>
     */
    protected array $instances = [];

    public function get(string $name = 'main', ?Stopwatch $stopwatch = null): Profiler
    {
        return $this->instances[$name] ??= $this->create($name, $stopwatch);
    }

    public function remove(string $name): static
    {
        unset($this->instances[$name]);

        return $this;
    }


    public function clear(): static
    {
        $this->instances = [];

        return $this;
    }

    public function create(string $name, ?Stopwatch $stopwatch = null): Profiler
    {
        return new Profiler($name, $stopwatch);
    }

    /**
     * @return array
     */
    public function getInstances(): array
    {
        return $this->instances;
    }
}
