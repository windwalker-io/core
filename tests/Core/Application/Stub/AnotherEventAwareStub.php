<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application\Stub;

use Windwalker\Core\Event\CoreEventAwareTrait;
use Windwalker\Event\EventAwareInterface;

/**
 * A second EventAwareInterface implementer, kept unregistered in the container
 * so ApplicationTrait::handleListener()'s `extendForCreate()` (container->has()
 * false) branch can be exercised via Container::newInstance().
 */
class AnotherEventAwareStub implements EventAwareInterface
{
    use CoreEventAwareTrait;
}
