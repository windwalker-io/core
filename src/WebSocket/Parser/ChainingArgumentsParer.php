<?php

declare(strict_types=1);

namespace Windwalker\WebSocket\Parser;

use Windwalker\Reactor\WebSocket\WebSocketRequestInterface;

/**
 * The ChainingArgumentsParer class.
 */
class ChainingArgumentsParer implements WebSocketParserInterface
{
    public function parse(string $data): array
    {
        $args = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

        if (!array_is_list($args)) {
            throw new \RuntimeException('Data should be a JSON array');
        }

        $name = array_shift($args);

        return compact('name', 'args');
    }

    public function format(...$args): string
    {
        if (array_is_list($args)) {
            return json_encode($args, JSON_THROW_ON_ERROR);
        }

        $name = (string) ($args['name'] ?? $args[0] ?? '');
        $args = $args['args'] ?? $args[1] ?? null;

        return json_encode([$name, ...$args], JSON_THROW_ON_ERROR);
    }

    public function handleRequest(WebSocketRequestInterface $request): WebSocketRequestInterface
    {
        [
            'name' => $name,
            'args' => $args,
        ] = $this->parse($request->getData());

        return $request->withRequestTarget($name)
            ->withParsedData($args);
    }
}
