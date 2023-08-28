<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\CliServer\Contracts;

interface ServerProcessManageInterface
{
    public function isRunning(): bool;

    public function reloadServer(): void;

    public function stopServer(): bool;
}
