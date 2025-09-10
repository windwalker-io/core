<?php

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Attribute;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\Context\AppContextInterface;
use Windwalker\Core\Controller\DelegatingController;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;

/**
 * The Controller class.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Controller implements ContainerAttributeInterface
{
    /**
     * Controller constructor.
     *
     * @param  string|null  $config
     * @param  array        $views
     */
    public function __construct(
        public ?string $config = null,
        public array $views = []
    ) {
    }

    /**
     * __invoke
     *
     * @param  AttributeHandler  $handler
     *
     * @return  callable
     */
    public function __invoke(AttributeHandler $handler): callable
    {
        $container = $handler->container;

        if ($this->config) {
            $config = $this->config;

            if (is_file($config)) {
                $container->registerByConfig($this->config);
            }
        }

        return fn(...$args): DelegatingController => (new DelegatingController(
            $container->get(AppContextInterface::class),
            $handler(...$args)
        ))
            ->setViewMap($this->views);
    }
}
