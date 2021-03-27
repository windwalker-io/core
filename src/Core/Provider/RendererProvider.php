<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Windwalker\Core\Renderer\Edge\WindwalkerExtension;
use Windwalker\Core\Renderer\RendererService;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Edge\Edge;
use Windwalker\Renderer\CompositeRenderer;
use Windwalker\Renderer\EdgeRenderer;
use Windwalker\Renderer\RendererInterface;

/**
 * The ViewProvider class.
 */
class RendererProvider implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param  Container  $container  The DI container.
     *
     * @return  void
     * @throws \Windwalker\DI\Exception\DefinitionException
     */
    public function register(Container $container): void
    {
        $container->prepareObject(
            CompositeRenderer::class,
            function (CompositeRenderer $renderer) use ($container) {
                $renderer->setPaths($container->getParam('renderer.paths') ?? []);
                $renderer->setFactories($container->getParam('renderer.renderers') ?? []);
                $renderer->setOptions($container->getParam('renderer.options') ?? []);

                $renderer->extend(
                    function (RendererInterface $renderer, array $options) use ($container) {
                        return $this->extendRenderer($container, $renderer, $options);
                    }
                );

                return $renderer;
            }
        )
            ->alias(RendererInterface::class, CompositeRenderer::class);

        $container->prepareSharedObject(RendererService::class);
    }

    public function extendRenderer(
        Container $container,
        RendererInterface $renderer,
        array $options
    ): RendererInterface {
        if ($renderer instanceof EdgeRenderer) {
            $renderer->extend(
                function (Edge $edge, array $options) use ($container) {
                    $edge->addExtension(
                        $container->newInstance(WindwalkerExtension::class)
                    );
                    return $edge;
                }
            );
        }

        return $renderer;
    }
}
