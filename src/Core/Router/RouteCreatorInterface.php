<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Router;

/**
 * Interface RouteCreatorInterface
 */
interface RouteCreatorInterface
{
    public function load(string|iterable|callable $paths): static;

    public function loadFolder(string|array $paths): static;

    public function register(callable $callable): static;

    public function compileRoutes(): array;
}
