<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Events\Web;

use Windwalker\Core\Application\WebApplication;
use Windwalker\DI\Container;
use Windwalker\Event\AbstractEvent;

/**
 * The TerminatingEvent class.
 */
class TerminatingEvent extends AbstractEvent
{
    protected WebApplication $app;

    protected Container $container;

    /**
     * @return WebApplication
     */
    public function getApp(): WebApplication
    {
        return $this->app;
    }

    /**
     * @param  WebApplication  $app
     *
     * @return  static  Return self to support chaining.
     */
    public function setApp(WebApplication $app): static
    {
        $this->app = $app;

        return $this;
    }

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * @param  Container  $container
     *
     * @return  static  Return self to support chaining.
     */
    public function setContainer(Container $container): static
    {
        $this->container = $container;

        return $this;
    }
}
