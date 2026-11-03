<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Package;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Windwalker\DI\Container;
use Windwalker\Tests\Core\Package\Stub\FallbackNamePackage;
use Windwalker\Tests\Core\Package\Stub\NamedPackage;
use Windwalker\Tests\Core\Package\Stub\WithComposerJson\Src\WithComposerJsonPackage;
use Windwalker\Utilities\Str;
use Windwalker\Utilities\StrNormalize;

class AbstractPackageTest extends TestCase
{
    #[Test]
    public function dirReturnsTheDirectoryOfTheClassFile(): void
    {
        self::assertSame(
            __DIR__ . DIRECTORY_SEPARATOR . 'Stub',
            FallbackNamePackage::dir()
        );
    }

    #[Test]
    public function rootReturnsTheParentOfDir(): void
    {
        self::assertSame(
            __DIR__,
            FallbackNamePackage::root()
        );
    }

    #[Test]
    public function pathReturnsRootWhenSuffixIsEmpty(): void
    {
        self::assertSame(
            FallbackNamePackage::root(),
            FallbackNamePackage::path()
        );
        self::assertSame(
            FallbackNamePackage::root(),
            FallbackNamePackage::path('')
        );
    }

    #[Test]
    public function pathAppendsSuffixToRoot(): void
    {
        self::assertSame(
            FallbackNamePackage::root() . DIRECTORY_SEPARATOR . 'foo/bar.php',
            FallbackNamePackage::path('foo/bar.php')
        );
    }

    #[Test]
    public function getNameFallsBackToKebabCasedClassNameWithoutPackageSuffix(): void
    {
        $expected = StrNormalize::toKebabCase(
            Str::removeEnd(FallbackNamePackage::class, 'Package')
        );

        self::assertSame($expected, FallbackNamePackage::getName());
    }

    #[Test]
    public function getNameReturnsExplicitStaticNameWhenSet(): void
    {
        self::assertSame('acme/explicit-name', NamedPackage::getName());
    }

    #[Test]
    public function composerJsonReturnsNullWhenFileDoesNotExist(): void
    {
        self::assertNull(FallbackNamePackage::composerJson());
    }

    #[Test]
    public function composerJsonReadsAndCachesTheNeighbouringComposerJsonFile(): void
    {
        $json = WithComposerJsonPackage::composerJson();

        self::assertSame(['name' => 'acme/with-composer-json'], $json);

        // Second call should hit the static cache and return the same data.
        self::assertSame($json, WithComposerJsonPackage::composerJson());
    }

    #[Test]
    public function getNameFallsBackToComposerJsonNameWhenStaticNameIsNotSet(): void
    {
        self::assertSame('acme/with-composer-json', WithComposerJsonPackage::getName());
    }

    #[Test]
    public function mergeConfigMergesNewDataOverExistingParametersByDefault(): void
    {
        $container = new Container();
        $container->getParameters()->fill(['foo' => 'original', 'baz' => 'kept']);

        $package = new FallbackNamePackage();
        $this->invokeMergeConfig($package, $container, ['foo' => 'merged', 'bar' => 'added']);

        self::assertEquals(
            ['foo' => 'merged', 'baz' => 'kept', 'bar' => 'added'],
            $container->getParameters()->dump()
        );
    }

    #[Test]
    public function mergeConfigKeepsExistingParametersWhenOverrideIsTrue(): void
    {
        $container = new Container();
        $container->getParameters()->fill(['foo' => 'original', 'baz' => 'kept']);

        $package = new FallbackNamePackage();
        $this->invokeMergeConfig($package, $container, ['foo' => 'merged', 'bar' => 'added'], true);

        self::assertEquals(
            ['foo' => 'original', 'baz' => 'kept', 'bar' => 'added'],
            $container->getParameters()->dump()
        );
    }

    private function invokeMergeConfig(
        FallbackNamePackage $package,
        Container $container,
        array $data,
        bool $override = false
    ): void {
        $ref = new ReflectionMethod($package, 'mergeConfig');
        $ref->invoke($package, $container, $data, $override);
    }
}
