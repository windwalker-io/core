<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\View;

use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Service\RendererService;
use Windwalker\Core\View\Event\AfterRenderEvent;
use Windwalker\Core\View\Event\BeforeRenderEvent;
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\Autowire;
use Windwalker\DI\Attributes\ContainerAttributeInterface;
use Windwalker\DI\Container;
use Windwalker\Event\EventAwareInterface;
use Windwalker\Event\EventAwareTrait;
use Windwalker\Renderer\CompositeRenderer;
use Windwalker\Renderer\RendererInterface;
use Windwalker\Utilities\Iterator\PriorityQueue;

/**
 * The ViewModel class.
 */
class View implements EventAwareInterface
{
    use EventAwareTrait;

    protected string $layout = '';

    protected ?RendererInterface $renderer = null;

    /**
     * View constructor.
     *
     * @param  object           $viewModel
     * @param  AppContext       $app
     * @param  RendererService  $rendererService
     */
    public function __construct(
        protected object $viewModel,
        protected AppContext $app,
        protected RendererService $rendererService,
    ) {
        //
    }

    public function render(array $data = []): string
    {
        $layout = $this->getLayout();

        $vm = $this->getViewModel();

        if (!$vm instanceof ViewModelInterface && class_exists($vm, 'prepare')) {
            throw new \LogicException(
                sprintf(
                    '%s must implement %s or has prepare() method.',
                    $vm::class,
                    ViewModelInterface::class
                )
            );
        }

        $event = $this->emit(
            BeforeRenderEvent::class,
            [
                'view' => $this,
                'viewModel' => $vm,
                'data' => $data,
                'state' => $this->app->getState()
            ]
        );

        $state = $vm->prepare($event->getState(), $this->app);

        $data = array_merge($this->rendererService->getGlobals(), $event->getData(), $state);
        
        $this->preparePaths($vm);
        
        $content = $this->getRenderer()->render($layout, $data, ['context' => $event->getViewModel()]);

        $event = $this->emit(
            AfterRenderEvent::class,
            [
                'view' => $this,
                'viewModel' => $vm,
                'data' => $data,
                'content' => $content
            ]
        );

        return $event->getContent();
    }

    /**
     * @return string
     */
    public function getLayout(): string
    {
        return $this->layout;
    }

    /**
     * @param  string  $layout
     *
     * @return  static  Return self to support chaining.
     */
    public function setLayout(string $layout)
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * @return object|null
     */
    public function getViewModel(): ?object
    {
        return $this->viewModel;
    }

    /**
     * @param  object  $viewModel
     *
     * @return  static  Return self to support chaining.
     */
    public function setViewModel(object $viewModel): static
    {
        $this->viewModel = $viewModel;

        return $this;
    }

    public function addPath(string $path, int $priority = 100): static
    {
        $renderer = $this->getRenderer();

        if ($renderer instanceof CompositeRenderer) {
            $renderer->addPath($path, $priority);
        }

        return $this;
    }

    /**
     * @return RendererInterface
     */
    public function getRenderer(): RendererInterface
    {
        return $this->renderer ??= $this->rendererService->createRenderer();
    }

    /**
     * @param  RendererInterface|null  $renderer
     *
     * @return  static  Return self to support chaining.
     */
    public function setRenderer(RendererInterface|null $renderer): static
    {
        $this->renderer = $renderer;

        return $this;
    }

    protected function preparePaths(object $vm): void
    {
        $ref = new \ReflectionClass($vm);
        $dir = dirname($ref->getFileName()) . '/views';

        if (is_dir($dir)) {
            $this->addPath($dir, PriorityQueue::HIGH);
        }
    }
}
