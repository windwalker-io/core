<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Events\Web;

use Psr\Http\Message\ResponseInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\Context\RequestAppContextInterface;
use Windwalker\Event\AbstractEvent;

/**
 * The AfterControllerDispatchEvent class.
 */
class AfterControllerDispatchEvent extends AbstractEvent
{
    protected ResponseInterface $response;

    protected RequestAppContextInterface $app;

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

    /**
     * @return RequestAppContextInterface
     */
    public function getApp(): RequestAppContextInterface
    {
        return $this->app;
    }

    /**
     * @param  RequestAppContextInterface  $app
     *
     * @return  static  Return self to support chaining.
     */
    public function setApp(RequestAppContextInterface $app): static
    {
        $this->app = $app;

        return $this;
    }
}
