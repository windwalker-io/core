<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\WebSocket;

use Windwalker\Reactor\WebSocket\WebSocketRequestInterface;

/**
 * Interface WsDispatcherInterface
 */
interface WebSocketParserInterface
{
    public function parse(string $data): mixed;

    public function format(mixed ...$args): string;

    public function handleRequest(WebSocketRequestInterface $request): WebSocketRequestInterface;
}
