<?php

declare(strict_types=1);

namespace Windwalker\Core\Events\Web;

use Windwalker\DI\Container;
use Windwalker\Event\BaseEvent;
use Windwalker\Utilities\Accessible\AccessorBCTrait;

/**
 * The AfterRespondEvent class.
 */
class AfterRespondEvent extends BaseEvent
{
    use AccessorBCTrait;

    /**
     * @param  Container  $container
     */
    public function __construct(public Container $container)
    {
    }
}
