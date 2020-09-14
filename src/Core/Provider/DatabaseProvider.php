<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Windwalker\Core\Manager\DatabaseManager;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;

/**
 * The DatabaseProvider class.
 */
class DatabaseProvider implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param  Container  $container  The DI container.
     *
     * @return  void
     */
    public function register(Container $container): void
    {
        $container->prepareSharedObject(DatabaseManager::class);
        $container->bind(DatabaseAdapter::class, fn (DatabaseManager $manager) => $manager->get());
    }
}
