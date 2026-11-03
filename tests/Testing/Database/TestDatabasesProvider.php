<?php

declare(strict_types=1);

namespace Windwalker\Tests\Testing\Database;

use Windwalker\Core\Factory\DatabaseServiceFactory;
use Windwalker\Core\Testing\Database\TestDatabaseFixtures;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\ORM\ORM;

class TestDatabasesProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->prepareSharedObject(TestDatabaseFixtures::class);

        $container->prepareSharedObject(
            TestDatabaseServiceFactory::class
        );
        $container->alias(DatabaseServiceFactory::class, TestDatabaseServiceFactory::class);

        $container->share(
            DatabaseAdapter::class,
            fn(Container $container, ?string $tag = null)
                => $container->get(TestDatabaseServiceFactory::class)->get($tag)
        );
        $container->share(
            ORM::class,
            fn(Container $container, ?string $tag = null)
                => $container->get(TestDatabaseServiceFactory::class)->getORM($tag)
        );
    }
}
