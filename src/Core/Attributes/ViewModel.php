<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Attributes;

use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\View\Event\BeforeRenderEvent;
use Windwalker\Core\View\View;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\ContainerAttributeInterface;
use Windwalker\DI\Container;

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

            return $this->registerEvents(
                $container->newInstance(
                    View::class,
                    compact('viewModel')
                )
                    ->setLayout($this->layout),
                $container
            );
        };
    }

    protected function registerEvents(View $view, Container $container): View
    {
        $view->on(
            BeforeRenderEvent::class,
            function (BeforeRenderEvent $event) use ($container) {
                $asset = $container->resolve(AssetService::class);

                foreach ($this->css as $name => $css) {
                    $asset->addCSS($css);
                }

                foreach ($this->js as $name => $js) {
                    $path = '@view/' . $js;

                    $asset->addModule($path);

                    // $asset->internalJS(
                    //     sprintf(
                    //         "import('%s')",
                    //         $asset->path($path)
                    //     )
                    // );
                }
            }
        );

        return $view;
    }
}
