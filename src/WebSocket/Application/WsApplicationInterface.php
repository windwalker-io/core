<?php

declare(strict_types=1);

namespace Windwalker\WebSocket\Application;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Reactor\WebSocket\WebSocketRequestInterface;

/**
 * Interface WebSocketApplicationInterface
 */
interface WsApplicationInterface extends ApplicationInterface
{
    public function pushTo(int|array $fds, mixed ...$args): bool;

    public function pushRawTo(int|array $fds, string $data): bool;

    public function rememberRequest(WebSocketRequestInterface $request, ?int $fd = null): bool;

    public function getRequest(int $fd, ?WebSocketRequestInterface $request = null): WebSocketRequestInterface;

    public function forgetRequest(int $fd): bool;
}
