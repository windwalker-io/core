<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Package;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Package\PackageInstaller;
use Windwalker\Core\Package\PackageMigrator;
use Windwalker\Core\Package\PackageRegistry;
use Windwalker\Tests\Core\Package\Stub\NamedPackage;
use Windwalker\Tests\Core\Package\Stub\WithComposerJson\Src\WithComposerJsonPackage;

class PackageRegistryTest extends TestCase
{
    private function createApp(): ApplicationInterface
    {
        $fixtureDir = __DIR__ . '/Stub/RegistryFixture';

        $app = $this->createStub(ApplicationInterface::class);
        $app->method('path')->willReturnCallback(
            static fn(string $path) => match ($path) {
                '@root/composer.json' => $fixtureDir . '/composer.json',
                '@root/vendor/composer/installed.json' => $fixtureDir . '/vendor/composer/installed.json',
                default => $path,
            }
        );
        $app->method('make')->willReturnCallback(
            static fn(string $class) => new $class()
        );

        return $app;
    }

    #[Test]
    public function getInstallerLazilyCreatesAndCachesAPackageInstaller(): void
    {
        $registry = new PackageRegistry($this->createApp());

        $installer = $registry->getInstaller();

        self::assertInstanceOf(PackageInstaller::class, $installer);
        self::assertSame($installer, $registry->getInstaller());
    }

    #[Test]
    public function getMigratorLazilyCreatesAndCachesAPackageMigrator(): void
    {
        $registry = new PackageRegistry($this->createApp());

        $migrator = $registry->getMigrator();

        self::assertInstanceOf(PackageMigrator::class, $migrator);
        self::assertSame($migrator, $registry->getMigrator());
    }

    #[Test]
    public function discoverReadsRootAndInstalledComposerJsonAndInstantiatesPackages(): void
    {
        $registry = new PackageRegistry($this->createApp());

        $registry->discover();

        self::assertTrue($registry->discovered);
        self::assertSame('acme/root', $registry->rootComposerJson['name']);
        self::assertCount(2, $registry->installedJson['packages']);

        $packages = $registry->getPackages();

        self::assertArrayHasKey(NamedPackage::class, $packages);
        self::assertArrayHasKey(WithComposerJsonPackage::class, $packages);
        self::assertInstanceOf(NamedPackage::class, $packages[NamedPackage::class]);
        self::assertInstanceOf(WithComposerJsonPackage::class, $packages[WithComposerJsonPackage::class]);
    }

    #[Test]
    public function discoverIsIdempotent(): void
    {
        $registry = new PackageRegistry($this->createApp());

        $registry->discover();
        $packagesAfterFirstCall = $registry->getPackages();

        $registry->discover();

        self::assertSame($packagesAfterFirstCall, $registry->getPackages());
    }

    #[Test]
    public function getPackageNamesReturnsPackagesKeyedByGetName(): void
    {
        $registry = new PackageRegistry($this->createApp());

        $names = $registry->getPackageNames();

        self::assertSame(
            ['acme/explicit-name', 'acme/with-composer-json'],
            array_keys($names)
        );
        self::assertInstanceOf(NamedPackage::class, $names['acme/explicit-name']);
    }

    #[Test]
    public function getPackageFindsAPackageByItsName(): void
    {
        $registry = new PackageRegistry($this->createApp());

        $package = $registry->getPackage('acme/with-composer-json');

        self::assertInstanceOf(WithComposerJsonPackage::class, $package);
        self::assertNull($registry->getPackage('does/not-exist'));
    }

    #[Test]
    public function getComposerPackagesMapGroupsPackagesByTheOwningComposerPackageName(): void
    {
        $registry = new PackageRegistry($this->createApp());

        $map = $registry->getComposerPackagesMap();

        self::assertArrayHasKey('acme/root', $map);
        self::assertArrayHasKey('acme/with-composer-json', $map);
        self::assertInstanceOf(NamedPackage::class, $map['acme/root'][0]);
        self::assertInstanceOf(WithComposerJsonPackage::class, $map['acme/with-composer-json'][0]);
    }

    #[Test]
    public function prepareInstallDiscoversAndInstallsEveryPackageIntoAChildInstaller(): void
    {
        $registry = new PackageRegistry($this->createApp());

        $installer = $registry->prepareInstall();

        self::assertSame($registry->getInstaller(), $installer);
        self::assertArrayHasKey('acme/explicit-name', $installer->children);
        self::assertArrayHasKey('acme/with-composer-json', $installer->children);
    }

    #[Test]
    public function prepareMigrateDiscoversAndMigratesEveryPackageIntoAChildMigrator(): void
    {
        $registry = new PackageRegistry($this->createApp());

        $migrator = $registry->prepareMigrate();

        self::assertSame($registry->getMigrator(), $migrator);
        self::assertArrayHasKey('acme/explicit-name', $migrator->children);
        self::assertArrayHasKey('acme/with-composer-json', $migrator->children);
    }
}
