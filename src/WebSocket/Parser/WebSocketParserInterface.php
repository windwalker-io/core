<?php

declare(strict_types=1);

namespace Windwalker\WebSocket\Parser;

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
