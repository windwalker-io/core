<?php

/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2023 LYRASOFT.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Events\Web;

use Windwalker\DI\Container;
use Windwalker\Event\AbstractEvent;

/**
 * The AfterRespondEvent class.
 */
class AfterRespondEvent extends AbstractEvent
{
    protected Container $container;

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
