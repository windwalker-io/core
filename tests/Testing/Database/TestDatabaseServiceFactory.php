<?php

declare(strict_types=1);

namespace Windwalker\Tests\Testing\Database;

use Windwalker\Core\Factory\DatabaseServiceFactory;
use Windwalker\Database\DatabaseFactory;
use Windwalker\Database\Driver\DriverOptions;
use Windwalker\Database\Driver\Pdo\DsnHelper;

class TestDatabaseServiceFactory extends DatabaseServiceFactory
{
    public function getDefaultName(): ?string
    {
        return 'test';
    }

    protected function getDefaultFactory(string $name, ...$args): mixed
    {
        return static function () {
            $dsn = env('WINDWALKER_TEST_DB_DSN');

            if (!$dsn) {
                throw new \RuntimeException('Please set WINDWALKER_TEST_DB_DSN in phpunit.xml');
            }

            $optionsArray = DsnHelper::extract($dsn, $type);
            $options = DriverOptions::wrap($optionsArray);

            return new DatabaseFactory()->create($type, $options);
        };
    }
}
