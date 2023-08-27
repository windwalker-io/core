<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\CliServer;

/**
 * Interface CliServerInterface
 */
interface CliServerEngineInterface
{
    public function isRunning(): bool;

    public static function isSupported(): bool;

    public function run(string $host, int $port, array $options = []): int;
}
