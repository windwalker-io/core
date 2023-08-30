<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Application\WebSocket;

use Windwalker\Reactor\WebSocket\WebSocketRequestInterface;

/**
 * Interface WsDispatcherInterface
 */
interface WsClientAdapterInterface
{
    public function parseMessage(string $data): mixed;

    public function formatMessage(mixed ...$args): string;

    public function handleRequest(WebSocketRequestInterface $request): WebSocketRequestInterface;
}
