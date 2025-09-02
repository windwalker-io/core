<?php

declare(strict_types=1);

namespace Windwalker\Core\View\Event;

use Windwalker\Core\State\AppState;
use Windwalker\Core\View\View;
use Windwalker\Core\View\ViewModelInterface;
use Windwalker\Event\BaseEvent;
use Windwalker\Utilities\Accessible\AccessorBCTrait;

/**
 * The AbstractViewRenderEvent class.
 */
abstract class AbstractViewRenderEvent extends BaseEvent
{
    use AccessorBCTrait;

    public function __construct(
        public View $view,
        public object $viewModel,
        public AppState $state,
        public string $layout,
        public array $data,
    ) {
    }

    /**
     * @return array
     *
     * @deprecated  Use property instead.
     */
    public function &getData(): array
    {
        return $this->data;
    }

    /**
     * @return string
     *
     * @deprecated  Use property instead.
     */
    public function &getLayout(): string
    {
        return $this->layout;
    }
}
