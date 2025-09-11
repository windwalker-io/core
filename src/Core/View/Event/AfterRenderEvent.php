<?php

declare(strict_types=1);

namespace Windwalker\Core\View\Event;

use Psr\Http\Message\ResponseInterface;
use Windwalker\Core\State\AppState;
use Windwalker\Core\View\View;
use Windwalker\Core\View\ViewModelInterface;

/**
 * The AfterRenderEvent class.
 */
class AfterRenderEvent extends AbstractViewRenderEvent
{
    public function __construct(
        public string $content,
        public ResponseInterface $response,
        View $view,
        object $viewModel,
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

    /**
     * @param  string        $name
     * @param  string|array  $value
     *
     * @return  $this
     *
     * @deprecated  Use `$event->response = $event->response->withAddedHeader()` instead.
     */
    public function addHeader(string $name, string|array $value): static
    {
        // Init response
        foreach ((array) $value as $v) {
            $this->response = $this->response->withAddedHeader($name, $v);
        }

        return $this;
    }
}
