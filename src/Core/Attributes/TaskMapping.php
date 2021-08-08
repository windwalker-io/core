<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

/**
 * The TaskMapping class.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class TaskMapping
{
    /**
     * TaskMapping constructor.
     *
     * @param  array  $tasks
     * @param  array  $methods
     */
    public function __construct(public array $tasks = [], public array $methods = [])
    {
        $this->methods = array_map(
            'strtoupper',
            $this->methods
        );
    }

    public function processTask(string $method, ?string $task): ?string
    {
        if (isset($this->methods['*'])) {
            return $this->methods['*'];
        }

        $task = $this->tasks[$task] ?? $task;
        $task = $this->methods[strtoupper($method)] ?? $task;

        return $task;
    }
}
