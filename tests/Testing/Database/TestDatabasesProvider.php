<?php

declare(strict_types=1);

namespace Windwalker\Tests\Testing\Database;

use Windwalker\Core\Factory\DatabaseServiceFactory;
use Windwalker\Core\Testing\Database\TestDatabaseFixtures;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\DatabaseFactory;
use Windwalker\Database\Driver\DriverOptions;
use Windwalker\Database\Driver\Pdo\DsnHelper;
use Windwalker\DI\Container;
use Windwalker\DI\ServiceProviderInterface;
use Windwalker\ORM\ORM;

class TestDatabasesProvider implements ServiceProviderInterface
{
    public function __construct(protected ?string $dsn = null) {}

    public function register(Container $container): void
    {
        $container->prepareSharedObject(TestDatabaseFixtures::class);

        $container->share(
            TestDatabaseServiceFactory::class,
            fn(Container $container) => $container->newInstance(TestDatabaseServiceFactory::class)
                ->setDefaultFactory(
                    function () {
                        if (!$this->dsn) {
                            throw new \RuntimeException('Please set WINDWALKER_TEST_DB_DSN in phpunit.xml');
                        }

                        $optionsArray = DsnHelper::extract($this->dsn, $type);
                        $options = DriverOptions::wrap($optionsArray);

                        if ($type === 'sqlsrv' || $type === 'pdo_sqlsrv') {
                            $options->tls = [
                                'verify' => false,
                            ];
                        }

                        return new DatabaseFactory()->create($type, $options);
                    }
                )
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
