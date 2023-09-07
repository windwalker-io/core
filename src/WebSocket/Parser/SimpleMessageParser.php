<?php

/**
 * Part of cati project.
 *
 * @copyright  Copyright (C) 2023 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\WebSocket\Parser;

use Windwalker\Reactor\WebSocket\WebSocketRequestInterface;

/**
 * The SampleMessageParser class.
 */
class SimpleMessageParser implements WebSocketParserInterface
{
    public function parse(string $data): array
    {
        [$name, $payload] = json_decode($data, true, 512, JSON_THROW_ON_ERROR) + ['', ''];

        return compact('name', 'payload');
    }

    public function format(...$args): string
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
        ] = $this->parse($request->getData());

        return $request->withRequestTarget($name)
            ->withParsedData($payload);
    }
}
