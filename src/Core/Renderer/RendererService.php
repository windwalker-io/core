<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Renderer;

use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\DateTime\ChronosService;
use Windwalker\Core\Language\LangService;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\RouteUri;
use Windwalker\Core\Router\SystemUri;
use Windwalker\Core\Theme\ThemeInterface;
use Windwalker\Renderer\CompositeRenderer;
use Windwalker\Renderer\RendererInterface;
use Windwalker\Renderer\TemplateFactoryInterface;

/**
 * The RendererService class.
 */
class RendererService
{
    /**
     * RendererService constructor.
     *
     * @param  ApplicationInterface  $app
     * @param  LayoutPathResolver    $resolver
     * @param  array                 $globals
     */
    public function __construct(
        protected ApplicationInterface $app,
        protected LayoutPathResolver $resolver,
        protected array $globals = []
    ) {
    }

    public function render(string $layout, array $data, array $options = []): string
    {
        return $this->renderWith($this->createRenderer(), $layout, $data, $options);
    }

    public function renderWith(RendererInterface $renderer, string $layout, array $data, array $options = []): string
    {
        $data = array_merge($this->getGlobals(), $data);

        return $renderer->render($this->resolveLayout($layout), $data, $options);
    }

    public function make(string $layout, array $options = []): \Closure
    {
        return $this->createRenderer()->make($this->resolveLayout($layout), $options);
    }

    public function createRenderer(array $paths = []): RendererInterface|TemplateFactoryInterface
    {
        $renderer = $this->app->resolve(CompositeRenderer::class);

        foreach ($paths as $path) {
            $renderer->addPath($path);
        }

        return $renderer;
    }

    public function addGlobal(string $name, mixed $value): static
    {
        $this->globals[$name] = $value;

        return $this;
    }

    public function getGlobal(string $name): mixed
    {
        return $this->globals[$name] ?? null;
    }

    /**
     * @return array
     */
    public function getGlobals(): array
    {
        $globals = $this->globals;

        return $this->prepareGlobals($globals);
    }

    public function resolveLayout(string $layout): string
    {
        return $this->resolver->resolveLayout($layout);
    }

    protected function prepareGlobals(array $globals): array
    {
        // $globals['app'] = $this->app;
        // $globals['uri'] = $this->app->resolve(SystemUri::class);
        // $globals['chronos'] = $this->app->resolve(ChronosService::class);
        // $globals['asset'] = $this->app->resolve(AssetService::class);
        // $globals['theme'] = $this->app->resolve(ThemeInterface::class);
        // $globals['lang'] = $this->app->resolve(LangService::class);
        //
        // $navOptions = RouteUri::MODE_MUTE;
        //
        // if ($this->app->isDebug()) {
        //     $navOptions |= RouteUri::DEBUG_ALERT;
        // }
        //
        // $globals['nav'] = $this->app->resolve(Navigator::class)
        //     ->withOptions($navOptions);

        return $globals;
    }

    /**
     * @param  array  $globals
     *
     * @return  static  Return self to support chaining.
     */
    public function setGlobals(array $globals)
    {
        $this->globals = $globals;

        return $this;
    }
}
