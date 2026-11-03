<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Package;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Package\PackageMigrator;
use Windwalker\Core\Package\Record\PackageDbMigrate;

class PackageMigratorTest extends TestCase
{
    #[Test]
    public function getChildCreatesAndCachesChildMigrators(): void
    {
        $app = $this->createStub(ApplicationInterface::class);
        $migrator = new PackageMigrator('parent', $app);

        $child = $migrator->getChild('child');

        self::assertInstanceOf(PackageMigrator::class, $child);
        self::assertSame('child', $child->name);
        self::assertSame($child, $migrator->getChild('child'));
        self::assertSame(['child' => $child], $migrator->children);
    }

    #[Test]
    public function addDbMigrateCreatesAndStoresAPackageDbMigrate(): void
    {
        $app = $this->createStub(ApplicationInterface::class);
        $migrator = new PackageMigrator('parent', $app);

        $migrate = $migrator->addDbMigrate('1.0.0');

        self::assertInstanceOf(PackageDbMigrate::class, $migrate);
        self::assertSame('1.0.0', $migrate->version);
        self::assertSame([$migrate], $migrator->dbMigrates);
    }

    #[Test]
    public function addDbMigrateAppendsMultipleMigrates(): void
    {
        $app = $this->createStub(ApplicationInterface::class);
        $migrator = new PackageMigrator('parent', $app);

        $first = $migrator->addDbMigrate('1.0.0');
        $second = $migrator->addDbMigrate('2.0.0');

        self::assertSame([$first, $second], $migrator->dbMigrates);
    }
}
