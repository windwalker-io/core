<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    LGPL-2.0-or-later
 */

declare(strict_types=1);

namespace Windwalker\Core\View\Event;

use Psr\Http\Message\ResponseInterface;

/**
 * The BeforeRenderEvent class.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class BeforeRenderEvent extends AbstractViewRenderEvent
{
    protected mixed $response;

    /**
     * @return mixed
     */
    public function getResponse(): mixed
    {
        return $this->response;
    }

    /**
     * @param  mixed  $response
     *
     * @return  static  Return self to support chaining.
     */
    public function setResponse(mixed $response): static
    {
        $this->response = $response;

        return $this;
    }
}
