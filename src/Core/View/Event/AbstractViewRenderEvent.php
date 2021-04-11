<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\View\Event;

use Windwalker\Core\View\View;
use Windwalker\Core\View\ViewModelInterface;
use Windwalker\Data\Collection;
use Windwalker\Event\AbstractEvent;

/**
 * The AbstractViewRenderEvent class.
 */
class AbstractViewRenderEvent extends AbstractEvent
{
    protected View $view;

    protected string $layout;

    protected ViewModelInterface $viewModel;

    protected array $data = [];

    protected Collection $state;

    /**
     * @return View
     */
    public function getView(): View
    {
        return $this->view;
    }

    /**
     * @param  View  $view
     *
     * @return  static  Return self to support chaining.
     */
    public function setView(View $view): static
    {
        $this->view = $view;

        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param  array  $data
     *
     * @return  static  Return self to support chaining.
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return ViewModelInterface
     */
    public function getViewModel(): ViewModelInterface
    {
        return $this->viewModel;
    }

    /**
     * @param  ViewModelInterface  $viewModel
     *
     * @return  static  Return self to support chaining.
     */
    public function setViewModel(ViewModelInterface $viewModel)
    {
        $this->viewModel = $viewModel;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getState(): Collection
    {
        return $this->state;
    }

    /**
     * @param  Collection  $state
     *
     * @return  static  Return self to support chaining.
     */
    public function setState(Collection $state)
    {
        $this->state = $state;

        return $this;
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
    public function setLayout(string $layout): static
    {
        $this->layout = $layout;

        return $this;
    }
}
