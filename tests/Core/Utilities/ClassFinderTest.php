<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Utilities;

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\PathResolver;
use Windwalker\Core\Runtime\Config;
use Windwalker\Core\Utilities\ClassFinder;
use Windwalker\Filesystem\Filesystem;

class ClassFinderTest extends TestCase
{
    protected const string NS = 'Windwalker\\Tests\\Core\\Utilities\\ClassFinderFixture\\';

    private string $tmpDir;

    private ClassFinder $instance;

    #[Before]
    protected function prepareFixtures(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/ww-class-finder-test-' . uniqid('', true);

        mkdir($this->tmpDir . '/Sub', 0777, true);

        file_put_contents(
            $this->tmpDir . '/Foo.php',
            "<?php\nnamespace " . rtrim(self::NS, '\\') . ";\nclass Foo {}\n"
        );
        file_put_contents(
            $this->tmpDir . '/Bar.blade.php',
            "<?php\n// blade template, should be skipped by findClasses()\n"
        );
        file_put_contents(
            $this->tmpDir . '/Sub/Baz.php',
            "<?php\nnamespace " . rtrim(self::NS, '\\') . "Sub;\nclass Baz {}\n"
        );

        $loader = new ClassLoader();
        $loader->addPsr4(self::NS, $this->tmpDir);

        $this->instance = new ClassFinder(new PathResolver(new Config([])), $loader);
    }

    #[After]
    protected function removeFixtures(): void
    {
        if (is_dir($this->tmpDir)) {
            Filesystem::delete($this->tmpDir);
        }
    }

    #[Test]
    public function findDirsFromNamespaceResolvesPsr4Prefix(): void
    {
        $dirs = $this->instance->findDirsFromNamespace(self::NS . 'Sub');

        self::assertCount(1, $dirs);
        self::assertStringEndsWith('/Sub', str_replace('\\', '/', $dirs[0]));
    }

    #[Test]
    public function findDirsFromNamespaceReturnsEmptyArrayWhenNoPrefixMatches(): void
    {
        self::assertSame([], $this->instance->findDirsFromNamespace('Totally\\Unrelated\\Namespace'));
    }

    #[Test]
    public function findFilesNonRecursiveOnlyReturnsTopLevelFiles(): void
    {
        $files = [];

        foreach ($this->instance->findFiles(self::NS) as $file) {
            $files[] = $file->getFilename();
        }

        sort($files);

        self::assertSame(['Bar.blade.php', 'Foo.php'], $files);
    }

    #[Test]
    public function findFilesRecursiveIncludesSubdirectories(): void
    {
        $files = [];

        foreach ($this->instance->findFiles(self::NS, true) as $file) {
            $files[] = $file->getFilename();
        }

        sort($files);

        self::assertSame(['Bar.blade.php', 'Baz.php', 'Foo.php'], $files);
    }

    #[Test]
    public function findClassesSkipsBladeFilesAndNonRecursiveSubdirectories(): void
    {
        $classes = iterator_to_array($this->instance->findClasses(self::NS), false);

        self::assertSame([self::NS . 'Foo'], $classes);
    }

    #[Test]
    public function findClassesRecursiveIncludesNestedNamespacedClasses(): void
    {
        $classes = iterator_to_array($this->instance->findClasses(self::NS, true), false);

        sort($classes);

        self::assertSame(
            [self::NS . 'Foo', self::NS . 'Sub\\Baz'],
            $classes
        );
    }
}
