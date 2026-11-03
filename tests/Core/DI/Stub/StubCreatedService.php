<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\DI\Stub;

/**
 * Simple instantiable target used as a factory definition in
 * ServiceFactoryTraitTest and TaggingFactoryTest to verify the arguments
 * forwarded through create()/get(). Remove once those tests are removed.
 */
class StubCreatedService
{
    public function __construct(public string $instanceName = '', public string $tag = '') {}
}
