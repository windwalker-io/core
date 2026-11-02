<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application\Stub;

use Windwalker\Core\Application\ServiceAwareTrait;

/**
 * Test-only host class that exposes ServiceAwareTrait as a plain object.
 *
 * Used by ServiceAwareTraitTest. Remove it together with that test if the trait is removed.
 */
class ServiceAwareStub
{
    use ServiceAwareTrait;
}
