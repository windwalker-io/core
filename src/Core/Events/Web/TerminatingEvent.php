<?php

declare(strict_types=1);

namespace Windwalker\Core\Events\Web;

use Windwalker\Core\Application\RootApplicationInterface;
use Windwalker\DI\Container;
use Windwalker\Event\BaseEvent;
use Windwalker\Utilities\Accessible\AccessorBCTrait;

/**
 * The TerminatingEvent class.
 */
class TerminatingEvent extends BaseEvent
{
    use AccessorBCTrait;

    public function __construct(
        public RootApplicationInterface $app,
        public Container $container
    ) {
    }
}
