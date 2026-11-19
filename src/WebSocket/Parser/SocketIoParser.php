<?php

declare(strict_types=1);

namespace Windwalker\WebSocket\Parser;

use Windwalker\Reactor\WebSocket\WebSocketRequestInterface;

/**
 * Socket.IO v5 / Engine.IO v4 packet codec (text frames only).
 *
 * @see docs/socketio-parser.md
 */
class SocketIoParser implements WebSocketParserInterface
{
    // Engine.IO packet types
    public const int ENGINE_OPEN = 0;
    public const int ENGINE_CLOSE = 1;
    public const int ENGINE_PING = 2;
    public const int ENGINE_PONG = 3;
    public const int ENGINE_MESSAGE = 4;
    public const int ENGINE_UPGRADE = 5;
    public const int ENGINE_NOOP = 6;

    // Socket.IO packet types
    public const int CONNECT = 0;
    public const int DISCONNECT = 1;
    public const int EVENT = 2;
    public const int ACK = 3;
    public const int CONNECT_ERROR = 4;
    public const int BINARY_EVENT = 5;
    public const int BINARY_ACK = 6;

    /**
     * @return array{
     *     engine: int,
     *     type: ?int,
     *     namespace: string,
     *     id: ?int,
     *     attachments: int,
     *     name: ?string,
     *     args: array,
     *     data: mixed,
     * }
     */
    public function parse(string $data): array
    {
        if ($data === '' || !ctype_digit($data[0])) {
            throw new \RuntimeException('Invalid Engine.IO packet');
        }

        $engine = (int) $data[0];
        $rest = substr($data, 1);

        $packet = [
            'engine' => $engine,
            'type' => null,
            'namespace' => '/',
            'id' => null,
            'attachments' => 0,
            'name' => null,
            'args' => [],
            'data' => $engine === self::ENGINE_MESSAGE ? null : ($rest === '' ? null : $rest),
        ];

        if ($engine !== self::ENGINE_MESSAGE) {
            if ($engine === self::ENGINE_OPEN && $rest !== '') {
                $packet['data'] = json_decode($rest, true, 512, JSON_THROW_ON_ERROR);
            }

            return $packet;
        }

        if ($rest === '' || !ctype_digit($rest[0])) {
            throw new \RuntimeException('Invalid Socket.IO packet');
        }

        $type = (int) $rest[0];

        if ($type > self::BINARY_ACK) {
            throw new \RuntimeException('Unknown Socket.IO packet type: ' . $type);
        }

        $packet['type'] = $type;
        $i = 1;
        $len = strlen($rest);

        // Binary attachments: "<n>-"
        if ($type === self::BINARY_EVENT || $type === self::BINARY_ACK) {
            $dash = strpos($rest, '-', $i);

            if ($dash === false) {
                throw new \RuntimeException('Missing attachments count');
            }

            $packet['attachments'] = (int) substr($rest, $i, $dash - $i);
            $i = $dash + 1;
        }

        // Namespace: "/xxx,"
        if ($i < $len && $rest[$i] === '/') {
            $comma = strpos($rest, ',', $i);

            if ($comma === false) {
                $packet['namespace'] = substr($rest, $i);
                $i = $len;
            } else {
                $packet['namespace'] = substr($rest, $i, $comma - $i);
                $i = $comma + 1;
            }
        }

        // Ack id
        $start = $i;

        while ($i < $len && ctype_digit($rest[$i])) {
            $i++;
        }

        if ($i > $start) {
            $packet['id'] = (int) substr($rest, $start, $i - $start);
        }

        $json = substr($rest, $i);

        if ($json !== '') {
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $packet['data'] = $payload;

            if (in_array($type, [self::EVENT, self::BINARY_EVENT], true)) {
                if (!is_array($payload) || !array_is_list($payload) || !is_string($payload[0] ?? null)) {
                    throw new \RuntimeException('Event payload should be a JSON array starting with event name');
                }

                $packet['name'] = array_shift($payload);
                $packet['args'] = $payload;
            } elseif (in_array($type, [self::ACK, self::BINARY_ACK], true)) {
                $packet['args'] = is_array($payload) && array_is_list($payload) ? $payload : [$payload];
            }
        }

        return $packet;
    }

    /**
     * Named args: type (default EVENT), name, args, namespace, id, engine.
     * Positional: format('event', arg1, arg2) builds an EVENT on the default namespace.
     */
    public function format(mixed ...$args): string
    {
        if (array_is_list($args)) {
            $name = (string) array_shift($args);

            return $this->encode(self::EVENT, '/', null, [$name, ...$args]);
        }

        $engine = (int) ($args['engine'] ?? self::ENGINE_MESSAGE);

        if ($engine !== self::ENGINE_MESSAGE) {
            $data = $args['data'] ?? null;

            return $engine . (
                $data === null
                    ? ''
                    : (is_string($data) ? $data : json_encode($data, JSON_THROW_ON_ERROR))
            );
        }

        $type = (int) ($args['type'] ?? self::EVENT);
        $list = (array) ($args['args'] ?? []);

        if ($type === self::EVENT || $type === self::BINARY_EVENT) {
            $list = [(string) ($args['name'] ?? ''), ...$list];
        }

        $payload = match ($type) {
            self::EVENT, self::BINARY_EVENT, self::ACK, self::BINARY_ACK => $list,
            default => $args['data'] ?? null,
        };

        return $this->encode(
            $type,
            (string) ($args['namespace'] ?? '/'),
            isset($args['id']) ? (int) $args['id'] : null,
            $payload,
            (int) ($args['attachments'] ?? 0)
        );
    }

    private function encode(int $type, string $namespace, ?int $id, mixed $payload, int $attachments = 0): string
    {
        $str = (string) self::ENGINE_MESSAGE . $type;

        if ($attachments > 0) {
            $str .= $attachments . '-';
        }

        if ($namespace !== '/') {
            $str .= $namespace . ',';
        }

        if ($id !== null) {
            $str .= $id;
        }

        if ($payload !== null) {
            $str .= json_encode($payload, JSON_THROW_ON_ERROR);
        }

        return $str;
    }

    /**
     * EVENT packets: target = event name, parsedData = args list.
     * Other packets: target = "socket.io:<type>" / "engine.io:<type>" with the full packet as parsedData.
     */
    public function handleRequest(WebSocketRequestInterface $request): WebSocketRequestInterface
    {
        $packet = $this->parse($request->getData());

        if ($packet['name'] !== null) {
            $target = $packet['name'];
            $parsed = $packet['args'];
        } else {
            $target = $packet['type'] !== null
                ? 'socket.io:' . $packet['type']
                : 'engine.io:' . $packet['engine'];
            $parsed = $packet;
        }

        return $request->withRequestTarget($target)
            ->withParsedData($parsed)
            ->withAttribute('socketio', $packet);
    }
}
