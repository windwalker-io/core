<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\DI\Stub;

use Windwalker\Core\DI\ServiceFactoryInterface;
use Windwalker\Core\DI\ServiceFactoryTrait;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\Container;

/**
 * Test double exercising ServiceFactoryTrait directly, with configurable
 * config prefix and class name so ServiceFactoryTraitTest can control the
 * factory-path lookup rules. Remove once ServiceFactoryTraitTest is removed.
 */
class StubServiceFactory implements ServiceFactoryInterface
{
    use ServiceFactoryTrait;

    public function __construct(
        Config $config,
        Container $container,
        protected string $prefix = '',
        protected ?string $className = null
    ) {
        $this->config = $config->proxy($this->prefix);
        $this->container = $container;
    }

    public function getConfigPrefix(): string
    {
        return $this->prefix;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }
}
