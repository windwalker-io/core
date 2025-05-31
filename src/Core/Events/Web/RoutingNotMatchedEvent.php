<?php

declare(strict_types=1);

namespace Windwalker\Core\Events\Web;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\Core\Router\Exception\RouteNotFoundException;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Event\BaseEvent;
use Windwalker\Utilities\Accessible\AccessorBCTrait;

/**
 * The RoutingNotMatchedEvent class.
 */
class RoutingNotMatchedEvent extends BaseEvent
{
    use AccessorBCTrait;

    public function __construct(
        public SystemUri $systemUri,
        public ServerRequestInterface $request,
        public string $route,
        public RouteNotFoundException $exception
    ) {
    }
}
