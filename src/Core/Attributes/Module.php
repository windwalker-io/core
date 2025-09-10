<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Attribute;
use Windwalker\Core\Module\AbstractModule;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;

/**
 * The Module class.
 *
 * @deprecated  No replacement.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Module implements ContainerAttributeInterface
{
    /**
     * Module constructor.
     */
    public function __construct(public ?string $name = null, public string|array|null $config = null)
    {
    }

    public function __invoke(AttributeHandler $handler): callable
    {
        return function (...$args) use ($handler) {
            $container = $handler->container;

            if ($this->config) {
                $container->registerByConfig($this->config);
            }

            /** @var AbstractModule $module */
            $module = $handler(...$args);
            $module->setName($this->name);

            return $module;
        };
    }
}
