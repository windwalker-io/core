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
use Windwalker\Uri\Uri;

/**
 * The SocketIOAdapter class.
 */
class SocketIOAdapter implements WsClientAdapterInterface
{
    public function parseMessage(string $data): array
    {
        [$name, $payload] = json_decode($data, true, 512, JSON_THROW_ON_ERROR) + ['', ''];

        return compact('name', 'payload');
    }

    public function formatMessage(...$args): string
    {
        $name = (string) ($args['name'] ?? $args[0] ?? '');
        $payload = $args['payload'] ?? $args[1] ?? null;

        return json_encode([$name, $payload], JSON_THROW_ON_ERROR);
    }

    public function handleRequest(WebSocketRequestInterface $request): WebSocketRequestInterface
    {
        [
            'name' => $name,
            'payload' => $payload,
        ] = $this->parseMessage($request->getData());

        return $request->withUri((new Uri())->withPath($name))
            ->withParsedData($payload);
    }
}
