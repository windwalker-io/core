<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application\Stub;

/**
 * Test-only service with a constructor argument to verify container argument passing.
 *
 * Used by ServiceAwareTraitTest. Remove it together with that test.
 */
class CounterService
{
    public function __construct(public int $count = 0) {}
}
