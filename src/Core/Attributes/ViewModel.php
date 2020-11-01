<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Windwalker\Core\View\View;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;

/**
 * The ViewModel class.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ViewModel implements ContainerAttributeInterface
{
    /**
     * View constructor.
     *
     * @param  string|null  $layout
     * @param  array        $css
     * @param  array        $js
     */
    public function __construct(
        protected ?string $layout = null,
        protected array $css = [],
        protected array $js = [],
    ) {
        //
    }

    public function __invoke(AttributeHandler $handler): callable
    {
        $container = $handler->getContainer();

        return function (...$args) use ($container, $handler) {
            $viewModel = $handler(...$args);

            return $container->newInstance(
                View::class,
                compact('viewModel')
            )
                ->setLayout($this->layout);
        };
    }
}