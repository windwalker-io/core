<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Package;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Package\PackageInstaller;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Tests\Core\Package\Stub\MvcFixture\src\MvcFixturePackage;
use Windwalker\Tests\Core\Package\Stub\NamedPackage;

class PackageInstallerTest extends TestCase
{
    private string $tmpDir;

    #[Before]
    protected function prepareTmpDir(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/ww-package-installer-test-' . uniqid('', true);

        Filesystem::mkdir($this->tmpDir);
    }

    #[After]
    protected function removeTmpDir(): void
    {
        if (is_dir($this->tmpDir)) {
            Filesystem::delete($this->tmpDir);
        }
    }

    private function createApp(): ApplicationInterface
    {
        return $this->createStub(ApplicationInterface::class);
    }

    #[Test]
    public function namePropertyIsNullWhenNoPackageIsSet(): void
    {
        $installer = new PackageInstaller(null, $this->createApp());

        self::assertNull($installer->name);
    }

    #[Test]
    public function namePropertyDelegatesToPackageGetName(): void
    {
        $installer = new PackageInstaller(new NamedPackage(), $this->createApp());

        self::assertSame('acme/explicit-name', $installer->name);
    }

    #[Test]
    public function getChildCreatesAndCachesChildInstallersKeyedByPackageName(): void
    {
        $installer = new PackageInstaller(null, $this->createApp());
        $package = new NamedPackage();

        $child = $installer->getChild($package);

        self::assertInstanceOf(PackageInstaller::class, $child);
        self::assertSame($package, $child->package);
        self::assertSame($child, $installer->getChild($package));
        self::assertSame(['acme/explicit-name' => $child], $installer->children);
    }

    #[Test]
    public function installModulesCopiesFilesAndAppliesReplacesViaCallback(): void
    {
        $srcDir = $this->tmpDir . '/source';
        $destDir = $this->tmpDir . '/dest';

        Filesystem::mkdir($srcDir);
        Filesystem::write($srcDir . '/widget.php', 'namespace Acme\\Module\\Front;');

        $app = $this->createStub(ApplicationInterface::class);
        $app->method('path')->willReturnCallback(
            static fn(string $path) => $path === '@dest' ? $destDir : $path
        );

        $installer = new PackageInstaller(null, $app);

        $calls = [];
        $installer->installModules(
            [$srcDir . '/**/*' => '@dest'],
            ['Acme\\Module\\Front' => 'App\\Module\\Front'],
            ['front'],
            function (string $src, string $dest) use (&$calls) {
                $calls[] = [$src, $dest];
            }
        );

        $writtenFile = $destDir . '/widget.php';

        self::assertArrayHasKey('front', $installer->tags);
        self::assertCount(1, $installer->tags['front']->modules);
        self::assertSame($writtenFile, $installer->tags['front']->modules[realpath($srcDir . '/widget.php')]);
        self::assertSame($installer->tags['front']->modules, $installer->installResources->modules);

        // installModules() only registers the copy+replace callback; it does not
        // copy the file or run the callback immediately. The real copy is normally
        // done by the install command, so replicate it here before invoking the
        // callback manually to verify its replace+write behavior.
        self::assertFileDoesNotExist($writtenFile);

        Filesystem::copy($srcDir . '/widget.php', $writtenFile);

        $callback = $installer->tags['front']->getCallbacks()[0];
        $callback($srcDir . '/widget.php', $writtenFile);

        self::assertFileExists($writtenFile);
        self::assertSame('namespace App\\Module\\Front;', file_get_contents($writtenFile));
        self::assertCount(1, $calls);
        self::assertSame($srcDir . '/widget.php', $calls[0][0]);
        self::assertSame($writtenFile, $calls[0][1]);
    }

    #[Test]
    public function installMVCModulesCopiesModuleEntityAndRepositoryFiles(): void
    {
        $destDir = $this->tmpDir . '/dest';

        $app = $this->createStub(ApplicationInterface::class);
        $app->method('path')->willReturnCallback(
            static fn(string $path) => $destDir . str_replace('@source', '', $path)
        );

        $package = new MvcFixturePackage();
        $installer = new PackageInstaller($package, $app);

        $installer->installMVCModules('Widget', ['Front']);

        $ns = new ReflectionClass($package)->getNamespaceName();

        // installModules() only registers callbacks and path mappings; it does not
        // copy files. Replicate the copy the install command would normally do,
        // then run every stored callback so we can assert on the transformed result.
        foreach ($installer->installResources->modules as $src => $dest) {
            Filesystem::copy($src, $dest);
        }

        foreach ($installer->installResources->modules as $src => $dest) {
            foreach ($installer->getAllCallbacks() as $callback) {
                $callback($src, $dest);
            }
        }

        $moduleFile = $destDir . '/Module/Front/Widget/WidgetController.php';
        self::assertFileExists($moduleFile);
        self::assertStringNotContainsString($ns . '\\Module\\Front', file_get_contents($moduleFile));
        self::assertStringContainsString('App\\Module\\Front', file_get_contents($moduleFile));

        $entityFile = $destDir . '/Entity/Widget.php';
        self::assertFileExists($entityFile);
        self::assertStringContainsString('App\\Entity', file_get_contents($entityFile));

        $repositoryFile = $destDir . '/Repository/WidgetRepository.php';
        self::assertFileExists($repositoryFile);
        self::assertStringContainsString('App\\Repository', file_get_contents($repositoryFile));

        self::assertArrayHasKey('widget_front', $installer->tags);
        self::assertArrayHasKey('widget_model', $installer->tags);
    }

    #[Test]
    public function getAllCallbacksDedupesTheSameCallbackAcrossTags(): void
    {
        $srcDir = $this->tmpDir . '/source';
        $destDir = $this->tmpDir . '/dest';

        Filesystem::mkdir($srcDir);
        Filesystem::write($srcDir . '/a.php', 'a');

        $app = $this->createStub(ApplicationInterface::class);
        $app->method('path')->willReturn($destDir);

        $installer = new PackageInstaller(null, $app);

        $installer->installModules(
            [$srcDir . '/**/*' => '@dest'],
            [],
            ['tag-a', 'tag-b']
        );

        self::assertCount(1, $installer->getAllCallbacks());
        self::assertCount(1, $installer->getAllCallbacks(['tag-a', 'tag-b']));
        self::assertCount(0, $installer->getAllCallbacks(['unknown-tag']));
    }
}
