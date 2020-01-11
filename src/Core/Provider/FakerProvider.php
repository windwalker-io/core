<?php
/**
 * Part of earth project.
 *
 * @copyright  Copyright (C) 2019 LYRASOFT.
 * @license    LGPL-2.0-or-later
 */

namespace Windwalker\Core\Provider;

use Windwalker\Core\Seeder\FakerService;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The FakerProvider class.
 *
 * @since  3.5
 */
class FakerProvider implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container $container The DI container.
     *
     * @return  void
     */
    public function register(Container $container)
    {
        $container->prepareSharedObject(FakerService::class);
    }
}
