<?php

declare(strict_types=1);

namespace Windwalker\Core\Factory;

use Windwalker\Attributes\AttributeType;
use Windwalker\Core\DateTime\ServerTimeCast;
use Windwalker\Core\DI\ServiceFactoryInterface;
use Windwalker\Core\DI\ServiceFactoryTrait;
use Windwalker\Database\DatabaseAdapter;
use Windwalker\Database\DatabaseFactory;
use Windwalker\Database\Driver\DriverOptions;
use Windwalker\Database\Driver\Pdo\DsnHelper;
use Windwalker\Database\Platform\MySQLPlatform;
use Windwalker\DI\Attributes\Factory;
use Windwalker\DI\Attributes\Isolation;
use Windwalker\DI\Container;
use Windwalker\DI\Definition\ObjectBuilderDefinition;
use Windwalker\ORM\ORM;

use function Windwalker\DI\create;
use function Windwalker\value;

#[Isolation]
class DatabaseServiceFactory implements ServiceFactoryInterface
{
    use ServiceFactoryTrait {
        create as protected parentCreate;
    }

    public function getClassName(): ?string
    {
        return DatabaseAdapter::class;
    }

    public function getConfigPrefix(): string
    {
        return 'database';
    }

    public function configureORM(ORM $orm): void
    {
        $orm->setAttributesResolver($this->container->getAttributesResolver());

        $orm->getCaster()
            ->setDbTimezone(
                $this->container->getParam('app.server_timezone') ?: 'UTC'
            );

        $resolver = $orm->getAttributesResolver();
        $resolver->registerAttribute(ServerTimeCast::class, AttributeType::PROPERTIES);

        $attributes = (array) $this->config->getDeep('attributes');

        foreach ($attributes as $attribute => $type) {
            $resolver->registerAttribute($attribute, $type);
        }
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
        $db = $this->parentCreate($name, ...$args);

        $db->getDriver()->options->debug = WINDWALKER_DEBUG;

        $orm = $db->orm();

        $this->configureORM($orm);

        $platform = $db->getPlatform();

        if ($platform instanceof MySQLPlatform && $platform->getName() === $platform::MYSQL) {
            $options = $db->getOptions();

            try {
                if ($options->strict ?? true) {
                    $platform->enableStrictMode($options->modes);
                } elseif ($options->modes) {
                    $platform->setModes($options->modes);
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
        return #[Factory] static function (Container $container) use ($instanceName) {
            $factory = $container->newInstance(DatabaseFactory::class);
            $connConfig = $container->parameters->proxy('database.connections.' . $instanceName);

            $driverKey = 'database.connection.driver.' . $instanceName;

            $options = value($connConfig->getDeep('options') ?? []);

            if ($container->has($driverKey)) {
                $driver = $container->get($driverKey);

                return $factory->create(
                    $driver,
                    $options,
                );
            }

            return $factory->create(
                $connConfig->getDeep('driver'),
                $options,
            );
        };
    }

    public static function mergeDsnToOptions(array|DriverOptions $options): DriverOptions
    {
        $options = clone DriverOptions::wrap($options);

        if ($options->dsn) {
            $options = $options->withMerge(DsnHelper::extract($options->dsn));
        }

        return $options;
    }
}
