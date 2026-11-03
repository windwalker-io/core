<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\DI\Stub;

use Windwalker\Core\DI\ServiceFactoryInterface;
use Windwalker\Core\DI\ServiceFactoryTrait;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\Container;

/**
 * Test double exercising ServiceFactoryTrait with a fixed getClassName(), so
 * ServiceFactoryTraitTest can verify the class-scoped
 * "factories.<class>.*" lookup path. Remove once ServiceFactoryTraitTest is
 * removed.
 */
class StubClassScopedServiceFactory implements ServiceFactoryInterface
{
    use ServiceFactoryTrait;

    public function __construct(Config $config, Container $container, protected string $className)
    {
        $this->config = $config;
        $this->container = $container;
    }

    public function getConfigPrefix(): string
    {
        return '';
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }
}
