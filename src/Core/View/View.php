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
use Windwalker\DI\Attributes\AttributeHandler;
use Windwalker\DI\Attributes\Autowire;
use Windwalker\DI\Attributes\ContainerAttributeInterface;
use Windwalker\DI\Container;
use Windwalker\Renderer\CompositeRenderer;
use Windwalker\Renderer\RendererInterface;

/**
 * The ViewModel class.
 */
class View
{
    protected string $layout = '';

    /**
     * View constructor.
     *
     * @param  object           $viewModel
     * @param  AppContext       $app
     * @param  RendererService  $renderer
     */
    public function __construct(
        protected object $viewModel,
        protected AppContext $app,
        protected RendererService $renderer,
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

        if ($vm instanceof ViewModelInterface) {
            $state = $vm->prepare($this->app);
        } else {
            $state = $this->app->call([$vm, 'prepare']);
        }

        $data = array_merge($data, $state);

        return $this->renderer->render($layout, $data, ['context' => $vm]);
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
}
