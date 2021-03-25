<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Events\Web;

use Windwalker\Core\Application\AppContext;
use Windwalker\Event\AbstractEvent;

/**
 * The BeforeControllerDispatchEvent class.
 */
class BeforeControllerDispatchEvent extends AbstractEvent
{
    protected mixed $controller;

    protected AppContext $app;

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
