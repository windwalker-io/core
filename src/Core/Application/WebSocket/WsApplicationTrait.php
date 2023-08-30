<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Application\WebSocket;

use Windwalker\Reactor\WebSocket\MessageEmitterInterface;

/**
 * Trait WebSocketApplicationTrait
 */
trait WsApplicationTrait
{
    public function pushTo(int $fd, mixed ...$args): bool
    {
        $data = $this->getWebSocketClient()->formatMessage(...$args);

        return $this->pushRawTo($fd, $data);
    }

    public function pushRawTo(int $fd, string $data): bool
    {
        return $this->getContainer()->get(MessageEmitterInterface::class)->emit($fd, $data);
    }

    public function getWebSocketClient(): WsClientAdapterInterface
    {
        return $this->service(WsClientAdapterInterface::class);
    }
}
