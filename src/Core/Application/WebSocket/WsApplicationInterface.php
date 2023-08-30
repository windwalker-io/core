<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Application\WebSocket;

use Windwalker\Core\Application\ApplicationInterface;

/**
 * Interface WebSocketApplicationInterface
 */
interface WsApplicationInterface extends ApplicationInterface
{
    public function pushTo(int $fd, mixed ...$args): bool;

    public function pushRawTo(int $fd, string $data): bool;
}
