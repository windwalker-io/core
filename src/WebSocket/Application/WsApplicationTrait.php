<?php

declare(strict_types=1);

namespace Windwalker\WebSocket\Application;

use Windwalker\Reactor\WebSocket\MessageEmitterInterface;
use Windwalker\WebSocket\Parser\WebSocketParserInterface;

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
}
