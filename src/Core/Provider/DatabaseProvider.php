<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2014 - 2016 LYRASOFT. All rights reserved.
 * @license    GNU Lesser General Public License version 3 or later. see LICENSE
 */

namespace Windwalker\Core\Provider;

use Windwalker\Core\Database\DatabaseAdapter;
use Windwalker\Core\Database\DatabaseService;
use Windwalker\Core\Database\Exporter\AbstractExporter;
use Windwalker\Database\DatabaseFactory;
use Windwalker\Database\Driver\AbstractDatabaseDriver;
use Windwalker\Database\Driver\Mysql\MysqlDriver;
use Windwalker\DataMapper\DatabaseContainer;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\Structure\Structure;

/**
 * Class WhoopsProvider
 *
 * @since 1.0
 */
class DatabaseProvider implements ServiceProviderInterface
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
        $container->prepareSharedObject(DatabaseService::class);

        $closure = function (Container $container) {
            $service = $container->get(DatabaseService::class);

            return $service->createConnection();
        };

        class_alias(AbstractDatabaseDriver::class, DatabaseAdapter::class);
        $container->share(AbstractDatabaseDriver::class, $closure);

        DatabaseContainer::setDb(function () use ($container) {
            return $container->get(AbstractDatabaseDriver::class);
        });

        // For Exporter
        $closure = function (Container $container) {
            $service = $container->get(DatabaseService::class);

            return $service->getExporter();
        };

        $container->share(AbstractExporter::class, $closure);
    }
}
