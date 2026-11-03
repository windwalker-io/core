<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application\Stub;

use Windwalker\Core\Event\CoreEventAwareTrait;
use Windwalker\Event\EventAwareInterface;

/**
 * Minimal EventAwareInterface implementer for ApplicationTrait::handleListener()
 * extend()/extendForCreate() branch tests.
 */
class EventAwareStub implements EventAwareInterface
{
    use CoreEventAwareTrait;
}
