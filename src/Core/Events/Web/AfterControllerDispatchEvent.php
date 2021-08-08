<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Events\Web;

use Psr\Http\Message\ResponseInterface;
use Windwalker\Core\Application\AppContext;
use Windwalker\Event\AbstractEvent;

/**
 * The AfterControllerDispatchEvent class.
 */
class AfterControllerDispatchEvent extends AbstractEvent
{
    protected ResponseInterface $response;

    protected AppContext $app;

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
     * @return AppContext
     */
    public function getApp(): AppContext
    {
        return $this->app;
    }

    /**
     * @param  AppContext  $app
     *
     * @return  static  Return self to support chaining.
     */
    public function setApp(AppContext $app): static
    {
        $this->app = $app;

        return $this;
    }
}
