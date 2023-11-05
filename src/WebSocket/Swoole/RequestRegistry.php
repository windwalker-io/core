<?php

declare(strict_types=1);

namespace Windwalker\WebSocket\Swoole;

use Swoole\Table;
use Windwalker\Reactor\WebSocket\WebSocketRequest;
use Windwalker\Reactor\WebSocket\WebSocketRequestInterface;
use Windwalker\Uri\Uri;

/**
 * The RequestTable class.
 */
class RequestRegistry
{
    public Table $table;

    public readonly int $size;

    protected ?\Closure $serializer = null;
    protected ?\Closure $unSerializer = null;

    public function __construct(?int $size = null, protected readonly int $dataSize = 2048)
    {
        // Default size is same as `ulimit -n`,
        // @see https://wiki.swoole.com/#/server/setting?id=max_conn-max_connection
        $this->size = $size ?? 100000;

        $this->table = new Table($this->size);

        $this->table->column('fd', Table::TYPE_INT);
        $this->table->column('data', Table::TYPE_STRING, $dataSize);

        $this->table->create();
    }

    /**
     * @throws \JsonException
     */
    public function store(int $fd, WebSocketRequestInterface $request): bool
    {
        if ($serializer = $this->getSerializer()) {
            $data = (string) $serializer($request);
        } else {
            $uri = (string) $request->getUri();
            $attributes = $request->getAttributes();
            $headers = $request->getHeaders();

            $data = json_encode(
                compact('uri', 'attributes', 'headers'),
                JSON_THROW_ON_ERROR
            );
        }

        if (strlen($data) > $this->dataSize) {
            throw new \RuntimeException('Too large request');
        }

        return $this->table->set((string) $fd, compact('fd', 'data'));
    }

    public function get(int $fd, ?WebSocketRequestInterface $request = null)
    {
        $item = $this->table->get((string) $fd);

        if ($item === false) {
            throw new \UnexpectedValueException("Request of: $fd not found.");
        }

        $data = $item['data'];

        if ($unSerializer = $this->getUnSerializer()) {
            $request = $unSerializer($data);
        } else {
            [
                'uri' => $uri,
                'attributes' => $attributes,
                'headers' => $headers,
            ] = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

            $request ??= new WebSocketRequest();
            $request = $request->withUri(new Uri($uri))
                ->withAttributes($attributes);

            foreach ($headers as $header => $values) {
                $request = $request->withHeader($header, $values);
            }
        }

        return $request;
    }

    /**
     * @throws \JsonException
     */
//     public function store(int $fd, WebSocketRequestInterface $request): bool
//     {
//         $data = \Windwalker\serialize($request);
// dump($data);
//         return $this->table->set((string) $fd, compact('fd', 'data'));
//     }
//
//     public function get(int $fd): WebSocketRequestInterface
//     {
//         $item = $this->table->get((string) $fd);
//         $data = $item['data'];
// dump($data);
//         return \Windwalker\unserialize($data, ['allowed_classes' => true]);
//     }

    public function exists(int $fd): bool
    {
        return $this->table->exists((string) $fd);
    }

    public function remove(int $fd): bool
    {
        return $this->table->delete((string) $fd);
    }

    public function getSerializer(): ?\Closure
    {
        return $this->serializer;
    }

    /**
     * @param  \Closure|null  $serializer
     *
     * @return  static  Return self to support chaining.
     */
    public function setSerializer(?\Closure $serializer): static
    {
        $this->serializer = $serializer;

        return $this;
    }

    public function getUnSerializer(): ?\Closure
    {
        return $this->unSerializer;
    }

    /**
     * @param  \Closure|null  $unSerializer
     *
     * @return  static  Return self to support chaining.
     */
    public function setUnSerializer(?\Closure $unSerializer): static
    {
        $this->unSerializer = $unSerializer;

        return $this;
    }
}
