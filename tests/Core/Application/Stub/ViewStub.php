<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application\Stub;

use Psr\Http\Message\ResponseInterface;
use Windwalker\Http\Response\HtmlResponse;

/**
 * Minimal duck-typed view double for AppContext::renderView() - deliberately
 * does not depend on the real View/Renderer/Asset stack.
 */
class ViewStub
{
    public array $options = ['default' => true];

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function render(array $data): ResponseInterface
    {
        return new HtmlResponse(
            json_encode(['options' => $this->options, 'data' => $data], JSON_THROW_ON_ERROR)
        );
    }
}
