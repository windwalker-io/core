<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Edge\Stub;

use Windwalker\Core\Edge\Attribute\EdgeComponent;

/**
 * Fixture class carrying the #[EdgeComponent] attribute.
 *
 * Used by ComponentFinderTest to verify that ComponentFinder::scan() picks
 * up classes tagged with #[EdgeComponent]. Remove once that behavior is no
 * longer covered by a test.
 */
#[EdgeComponent('stub-component')]
class ComponentWithAttributeStub
{
}
