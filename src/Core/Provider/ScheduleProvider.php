<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 .
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Provider;

use Windwalker\Core\Schedule\Schedule;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The ScheduleProvider class.
 *
 * @since  3.5.3
 */
class ScheduleProvider implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param Container $container The DI container.
     *
     * @return  void
     */
    public function register(Container $container)
    {
        $container->prepareSharedObject(Schedule::class);
    }
}
