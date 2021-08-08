<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Controller\DelegatingController;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;

/**
 * The Controller class.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Controller implements ContainerAttributeInterface
{
    /**
     * Controller constructor.
     *
     * @param  string|null  $config
     * @param  string|null  $module
     * @param  array        $views
     */
    public function __construct(
        public ?string $config = null,
        public ?string $module = null,
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
        $container = $handler->getContainer();

        if ($this->config) {
            $config = $this->config;

            if (is_file($config)) {
                $container->registerByConfig($this->config);
            }
        }

        return fn(...$args): DelegatingController => (new DelegatingController(
            $container->get(AppContext::class),
            $handler(...$args)
        ))
            ->setModule($this->module)
            ->setViewMap($this->views);
    }
}
