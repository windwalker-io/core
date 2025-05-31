<?php

declare(strict_types=1);

namespace Windwalker\Core\Events\Web;

use Psr\Http\Message\ServerRequestInterface;
use Windwalker\DI\Container;
use Windwalker\Event\BaseEvent;
use Windwalker\Utilities\Accessible\AccessorBCTrait;

/**
 * The BeforeRequestEvent class.
 */
class BeforeRequestEvent extends BaseEvent
{
    use AccessorBCTrait;

    public function __construct(public Container $container, public ?ServerRequestInterface $request = null)
    {
    }
}
