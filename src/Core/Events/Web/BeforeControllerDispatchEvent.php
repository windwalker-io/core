<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Events\Web;

use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Application\Context\RequestAppContextInterface;
use Windwalker\Event\AbstractEvent;

/**
 * The BeforeControllerDispatchEvent class.
 */
class BeforeControllerDispatchEvent extends AbstractEvent
{
    protected mixed $controller;

    protected RequestAppContextInterface $app;

    /**
     * @return mixed
     */
    public function getController(): mixed
    {
        return $this->controller;
    }

    /**
     * @param  mixed  $controller
     *
     * @return  static  Return self to support chaining.
     */
    public function setController(mixed $controller): static
    {
        $this->controller = $controller;

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
