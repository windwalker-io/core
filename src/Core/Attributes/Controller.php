<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
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
     * @param  array        $views
     */
    public function __construct(
        protected ?string $config = null,
        protected array $views = [],
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
            $container->registerByConfig($this->config);
        }

        return static fn(...$args): DelegatingController => new DelegatingController(
            $container,
            $handler(...$args)
        );
    }
}
