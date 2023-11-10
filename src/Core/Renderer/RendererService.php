<?php

declare(strict_types=1);

namespace Windwalker\Core\Renderer;

use Closure;
use Windwalker\DI\Container;
use Windwalker\Renderer\CompositeRenderer;
use Windwalker\Renderer\RendererInterface;
use Windwalker\Renderer\TemplateFactoryInterface;
use Windwalker\Utilities\Iterator\PriorityQueue;

use function Windwalker\collect;

/**
 * The RendererService class.
 */
class RendererService
{
    /**
     * RendererService constructor.
     *
     * @param  Container           $container
     * @param  LayoutPathResolver  $pathResolver
     * @param  array               $globals
     */
    public function __construct(
        protected Container $container,
        protected LayoutPathResolver $pathResolver,
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

        return $renderer->render(
            $this->resolveLayout($layout),
            $data,
            $options
        );
    }

    public function make(string $layout, array $options = []): Closure
    {
        return $this->createRenderer()->make($this->resolveLayout($layout), $options);
    }

    public function createRenderer(array $paths = []): RendererInterface|TemplateFactoryInterface
    {
        $renderer = $this->container->resolve(CompositeRenderer::class);

        foreach ($paths as $path) {
            $renderer->addPath($path);
        }

        return $renderer;
    }

    public function getRendererFactories(): array
    {
        return $this->container->getParam('renderer.renderers') ?? [];
    }

    public function getSupportedExtensions(): array
    {
        return collect($this->getRendererFactories())
            ->map(fn(array $f) => $f[1])
            ->flatten()
            ->values()
            ->dump();
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
        return $this->pathResolver->resolveLayout($layout, $this->getSupportedExtensions());
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
    public function setGlobals(array $globals): static
    {
        $this->globals = $globals;

        return $this;
    }

    public function addPath(
        string|array $paths,
        ?int $priority = PriorityQueue::ABOVE_NORMAL,
        string $ns = 'main'
    ): static {
        $this->pathResolver->addPaths($paths, $priority, $ns);

        return $this;
    }

    public function getPaths(string $ns = 'main'): PriorityQueue
    {
        return $this->pathResolver->getPathsBag($ns)->getClonedPaths();
    }

    public function dumpPaths(string $ns = 'main'): array
    {
        return iterator_to_array(clone $this->getPaths($ns));
    }

    public function dumpAllPaths(): array
    {
        $paths = [];

        foreach ($this->pathResolver->getPathsBags() as $name => $bag) {
            $paths[$name] = iterator_to_array(clone $bag->getPaths());
        }

        return $paths;
    }
}
