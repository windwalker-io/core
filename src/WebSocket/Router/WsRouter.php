<?php

declare(strict_types=1);

namespace Windwalker\WebSocket\Router;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Router\Route;
use Windwalker\Core\Router\Router;
use Windwalker\Reactor\WebSocket\WebSocketRequestInterface;
use Windwalker\WebSocket\Application\WsAppContext;
use Windwalker\WebSocket\Application\WsApplicationInterface;
use Windwalker\WebSocket\Application\WsRootApplicationInterface;

/**
 * The WsRouter class.
 */
class WsRouter extends Router
{
    protected WsApplicationInterface $app;

    public function __construct(array $routes = [])
    {
        parent::__construct($routes);
    }

    public static function createRouteCreator(): WsRouterCreator
    {
        return new WsRouterCreator();
    }

    public function filterRoute(ServerRequestInterface $request, Route $route): bool
    {
        $ns = $route->getNamespace();

        if ($ns) {
            /** @var WebSocketRequestInterface $request */
            $rootRequest = $this->app->getRequest($request->getFd());

            $path = $rootRequest->getUri()->getPath();

            if (!$this->matchPattern($ns, $path, $vars)) {
                return false;
            }

            $route->vars($vars);
        }

        return parent::filterRoute($request, $route);
    }

    public function getApp(): WsApplicationInterface
    {
        return $this->app;
    }

    public function setApp(WsApplicationInterface $app): static
    {
        $this->app = $app;

        return $this;
    }
}
