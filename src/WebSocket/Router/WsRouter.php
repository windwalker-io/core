<?php

declare(strict_types=1);

namespace Windwalker\WebSocket\Router;

use Windwalker\Core\Router\Router;

/**
 * The WsRouter class.
 */
class WsRouter extends Router
{
    public static function createRouteCreator(): WsRouterCreator
    {
        return new WsRouterCreator();
    }
}
