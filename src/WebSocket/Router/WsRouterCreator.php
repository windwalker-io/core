<?php

declare(strict_types=1);

namespace Windwalker\WebSocket\Router;

use Windwalker\Core\Router\Route;
use Windwalker\Core\Router\RouteConfigurationTrait;
use Windwalker\Core\Router\RouteCreatorInterface;
use Windwalker\Core\Router\RouteCreatorTrait;
use Windwalker\Utilities\Classes\FlowControlTrait;

/**
 * The WsRouterCreator class.
 */
class WsRouterCreator implements RouteCreatorInterface
{
    use RouteConfigurationTrait;
    use RouteCreatorTrait;
    use FlowControlTrait;

    public function open(string $pattern, array $options = []): Route
    {
        return $this->any($pattern, null, $options)
            ->methods('OPEN');
    }

    public function message(string $pattern, array $options = []): Route
    {
        return $this->any($pattern, null, $options)
            ->methods('MESSAGE');
    }

    public function close(string $pattern, array $options = []): Route
    {
        return $this->any($pattern, null, $options)
            ->methods('CLOSE');
    }
}
