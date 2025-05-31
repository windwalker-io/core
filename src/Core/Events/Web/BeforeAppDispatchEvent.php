<?php

declare(strict_types=1);

namespace Windwalker\Core\Events\Web;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\DI\Container;
use Windwalker\Event\BaseEvent;
use Windwalker\Utilities\Accessible\AccessorBCTrait;

/**
 * The AppBeforeExecute class.
 */
class BeforeAppDispatchEvent extends BaseEvent
{
    use AccessorBCTrait;

    /**
     * @param  ServerRequestInterface  $request
     * @param  array|iterable          $middlewares
     * @param  Container               $container
     */
    public function __construct(
        public ServerRequestInterface $request,
        public Container $container,
        public iterable $middlewares = [],
    ) {
    }
}
