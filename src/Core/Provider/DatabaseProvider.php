<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2020 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Windwalker\Core\Provider;

use Faker\Factory;
use Faker\Generator;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Database\DatabaseExportService;
use Windwalker\Core\Manager\DatabaseManager;
use Windwalker\Core\Migration\MigrationService;
use Windwalker\Core\Seed\FakerService;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\DatabaseFactory;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\ORM\ORM;

use function Windwalker\DI\create;

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
     * @throws \Windwalker\DI\Exception\DefinitionException
     */
    public function register(Container $container): void
    {
        $container->prepareSharedObject(DatabaseManager::class);
        $container->prepareSharedObject(DatabaseFactory::class);
        $container->bind(DatabaseAdapter::class, fn(DatabaseManager $manager) => $manager->get());
        $container->bind(ORM::class, fn(DatabaseManager $manager) => $manager->get()->orm());

        // Faker
        $container->prepareSharedObject(FakerService::class);

        // Services
        $container->prepareSharedObject(DatabaseExportService::class);
        $container->prepareObject(MigrationService::class);
    }
}
