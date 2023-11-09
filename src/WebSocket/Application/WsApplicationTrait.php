<?php

declare(strict_types=1);

namespace Windwalker\WebSocket\Application;

use Windwalker\Reactor\WebSocket\MessageEmitterInterface;
use Windwalker\Reactor\WebSocket\WebSocketRequestInterface;
use Windwalker\WebSocket\Parser\WebSocketParserInterface;
use Windwalker\WebSocket\Swoole\RequestRegistry;

/**
 * Trait WebSocketApplicationTrait
 */
trait WsApplicationTrait
{
    public function pushTo(int|array $fds, mixed ...$args): bool
    {
        $data = $this->getParser()->format(...$args);

        return $this->pushRawTo($fds, $data);
    }

    public function pushRawTo(int|array $fds, string $data): bool
    {
        $emitter = $this->getContainer()->get(MessageEmitterInterface::class);

        $return = true;

        foreach ((array) $fds as $fd) {
            $return = $return && $emitter->emit($fd, $data);
        }

        return $return;
    }

    public function getParser(): WebSocketParserInterface
    {
        return $this->service(WebSocketParserInterface::class);
    }

    public function storeRequest(WebSocketRequestInterface $request, ?int $fd = null): bool
    {
        return $this->retrieve(RequestRegistry::class)->store($request, $fd);
    }

    public function getRequest(int $fd, ?WebSocketRequestInterface $request = null)
    {
        return $this->retrieve(RequestRegistry::class)->get($fd, $request);
    }

    public function removeRequest(int $fd)
    {
        return $this->retrieve(RequestRegistry::class)->remove($fd);
    }
}
