<?php

declare(strict_types=1);

namespace Windwalker\Core\Manager\Event;

use Windwalker\Event\BaseEvent;
use Windwalker\Utilities\Accessible\AccessorBCTrait;

/**
 * The InstanceCreatedEvent class.
 */
class InstanceCreatedEvent extends BaseEvent
{
    use AccessorBCTrait;

    public function __construct(
        public object $instance,
        public string $instanceName = '',
        public array $args = []
    ) {
    }
}
