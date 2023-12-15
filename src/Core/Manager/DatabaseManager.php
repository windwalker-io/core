<?php

declare(strict_types=1);

namespace Windwalker\Core\Manager;

use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\DatabaseFactory;
use Windwalker\Database\Platform\MySQLPlatform;
use Windwalker\DI\Attributes\Isolation;
use Windwalker\DI\Container;
use Windwalker\ORM\ORM;

/**
 * The DatabaseManager class.
 *
 * @method DatabaseAdapter get(?string $name = null, ...$args)
 */
#[Isolation]
class DatabaseManager extends AbstractManager
{
    public function getConfigPrefix(): string
    {
        return 'database';
    }

    protected function getFactoryPath(string $name): string
    {
        $path =  'connections.' . $name . '.factory';

        if (!$this->config->hasDeep($path)) {
            $path =  'connections.' . $name;
        }

        return $path;
    }

    protected function getLegacyFactoryPath(string $name): string
    {
        return 'connections.' . $name;
    }

    /**
     * create
     *
     * @param  string|null  $name
     * @param  mixed        ...$args
     *
     * @return  DatabaseAdapter
     */
    public function create(?string $name = null, ...$args): object
    {
        /** @var DatabaseAdapter $db */
        $db = parent::create($name, ...$args);

        $db->getDriver()->setOption('debug', WINDWALKER_DEBUG);

        $orm = $db->orm();

        $orm->setAttributesResolver($this->container->getAttributesResolver());

        $orm->getCaster()
            ->setDbTimezone(
                $this->container->getParam('app.server_timezone') ?: 'UTC'
            );

        $platform = $db->getPlatform();

        if ($platform instanceof MySQLPlatform && $platform->getName() === $platform::MYSQL) {
            $options = $db->getOptions();

            try {
                if ($options['strict'] ?? true) {
                    $platform->enableStrictMode($options['modes'] ?? null ?: null);
                } elseif ($options['modes'] ?? null) {
                    $platform->setModes($options['modes'] ?? []);
                }
            } catch (\RuntimeException $e) {
                // No actions
            }
        }

        return $db;
    }

    public function getORM(?string $name = null): ORM
    {
        return $this->get($name)->orm();
    }

    public function getDatabaseFactory(): DatabaseFactory
    {
        return $this->container->resolve(DatabaseFactory::class);
    }

    public static function createAdapter(string $instanceName): \Closure
    {
        return static function (Container $container) use ($instanceName) {
            $factory = $container->newInstance(DatabaseFactory::class);
            $connConfig = $container->getParam('database.connections.' . $instanceName);

            $driverKey = 'database.connection.driver.' . $instanceName;

            if ($container->has($driverKey)) {
                $driver = $container->get($driverKey);

                return $factory->create(
                    $driver,
                    $connConfig['options'] ?? [],
                );
            }

            return $factory->create(
                $connConfig['driver'],
                $connConfig['options'] ?? [],
            );
        };
    }
}
