<?php

declare(strict_types=1);

namespace Windwalker\Core\Events\Web;

use Psr\Http\Message\ResponseInterface;
use Windwalker\DI\Container;
use Windwalker\Event\BaseEvent;
use Windwalker\Utilities\Accessible\AccessorBCTrait;

/**
 * The AppAfterExecute class.
 */
class AfterRequestEvent extends BaseEvent
{
    use AccessorBCTrait;

    public function __construct(public Container $container, public ResponseInterface $response)
    {
    }
}
