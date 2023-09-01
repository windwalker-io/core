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
use Windwalker\Core\Application\Context\AppContextInterface;
use Windwalker\Event\AbstractEvent;

/**
 * The BeforeControllerDispatchEvent class.
 */
class BeforeControllerDispatchEvent extends AbstractEvent
{
    protected mixed $controller;

    protected AppContextInterface $app;

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
