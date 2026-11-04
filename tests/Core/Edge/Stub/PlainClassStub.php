<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Edge\Stub;

/**
 * Fixture class without the #[EdgeComponent] attribute.
 *
 * Used by ComponentFinderTest to verify that classes without the attribute
 * are excluded from scan results. Remove once that behavior is no longer
 * covered by a test.
 */
class PlainClassStub {}
