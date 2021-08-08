<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\View\Event;

use Psr\Http\Message\ResponseInterface;

/**
 * The AfterRenderEvent class.
 */
class AfterRenderEvent extends AbstractViewRenderEvent
{
    protected string $content;

    protected ResponseInterface $response;

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param  string  $content
     *
     * @return  static  Return self to support chaining.
     */
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * @param  ResponseInterface  $response
     *
     * @return  static  Return self to support chaining.
     */
    public function setResponse(ResponseInterface $response): static
    {
        $this->response = $response;

        return $this;
    }

    public function addHeader(string $name, string|array $value): static
    {
        // Init response
        foreach ((array) $value as $v) {
            $this->response = $this->response->withAddedHeader($name, $v);
        }

        return $this;
    }
}
