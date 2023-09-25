<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\WebSocket\Application;

use Windwalker\Core\Application\ApplicationInterface;

/**
 * Interface WebSocketApplicationInterface
 */
interface WsApplicationInterface extends ApplicationInterface
{
    public function pushTo(int|array $fds, mixed ...$args): bool;

    public function pushRawTo(int|array $fds, string $data): bool;
}
