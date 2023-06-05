<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Controller;

use ReflectionException;

/**
 * Interface ControllerInterface
 */
interface ControllerInterface
{
    /**
     * execute
     *
     * @param  string  $task
     * @param  array   $args
     *
     * @return mixed
     * @throws ReflectionException
     */
    public function execute(string $task, array $args = []): mixed;
}
