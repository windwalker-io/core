<?php

declare(strict_types=1);

namespace Windwalker\Core\View\Event;

use Attribute;
use Windwalker\Core\State\AppState;
use Windwalker\Core\View\View;
use Windwalker\Core\View\ViewModelInterface;

/**
 * The BeforeRenderEvent class.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class BeforeRenderEvent extends AbstractViewRenderEvent
{
    public function __construct(
        public mixed $response,
        View $view,
        ViewModelInterface $viewModel,
        AppState $state,
        string $layout,
        array $data
    ) {
        parent::__construct(
            view: $view,
            viewModel: $viewModel,
            state: $state,
            layout: $layout,
            data: $data
        );
    }
}
