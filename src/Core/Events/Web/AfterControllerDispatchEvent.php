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
use Windwalker\Core\Application\Context\AppContextInterface;
use Windwalker\Event\AbstractEvent;

/**
 * The AfterControllerDispatchEvent class.
 */
class AfterControllerDispatchEvent extends AbstractEvent
{
    protected mixed $response;

    protected AppContextInterface $app;

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

    /**
     * @return AppContextInterface
     */
    public function getApp(): AppContextInterface
    {
        return $this->app;
    }

    /**
     * @param  AppContextInterface  $app
     *
     * @return  static  Return self to support chaining.
     */
    public function setApp(AppContextInterface $app): static
    {
        $this->app = $app;

        return $this;
    }
}
