<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Renderer;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\PathResolver;
use Windwalker\Core\Renderer\LayoutNotFoundException;
use Windwalker\Core\Renderer\LayoutPathResolver;
use Windwalker\Core\Runtime\Config;
use Windwalker\DI\Container;
use Windwalker\Filesystem\Filesystem;
use Windwalker\Filesystem\Path;

class LayoutPathResolverTest extends TestCase
{
    private string $tmpDir;

    #[Before]
    protected function prepareTmpDir(): void
    {
        $this->tmpDir = WINDWALKER_TEMP . '/ww-layout-path-resolver-test-' . uniqid('', true);

        Filesystem::mkdir($this->tmpDir . '/layouts');
        Filesystem::mkdir($this->tmpDir . '/widgets');
    }

    #[After]
    protected function removeTmpDir(): void
    {
        if (is_dir($this->tmpDir)) {
            Filesystem::delete($this->tmpDir);
        }
    }

    #[Test]
    public function constructorLoadsAliasesFromContainerParamWhenNotProvided(): void
    {
        $container = $this->createContainer(['renderer' => ['aliases' => ['@foo' => 'bar']]]);
        $resolver = new LayoutPathResolver($container, $this->createPathResolver());

        self::assertSame(['@foo' => 'bar'], $resolver->getAliases());
    }

    #[Test]
    public function constructorExplicitAliasesOverrideContainerParam(): void
    {
        $container = $this->createContainer(['renderer' => ['aliases' => ['@foo' => 'bar']]]);
        $resolver = new LayoutPathResolver($container, $this->createPathResolver(), ['@explicit' => 'x']);

        self::assertSame(['@explicit' => 'x'], $resolver->getAliases());
    }

    #[Test]
    public function constructorLoadsPathsAndNamespacesFromContainerParams(): void
    {
        $container = $this->createContainer(
            [
                'renderer' => [
                    'paths' => ['@root/layouts'],
                    'namespaces' => ['widgets' => ['@root/widgets']],
                ],
            ]
        );
        $resolver = new LayoutPathResolver($container, $this->createPathResolver());

        self::assertSame(
            [Path::normalize($this->tmpDir . '/layouts')],
            $resolver->getPathsBag('main')->dumpPaths()
        );
        self::assertSame(
            [Path::normalize($this->tmpDir . '/widgets')],
            $resolver->getPathsBag('widgets')->dumpPaths()
        );
    }

    #[Test]
    public function resolveLayoutFindsFileInMainNamespace(): void
    {
        file_put_contents($this->tmpDir . '/layouts/hello.php', '<?php');

        $resolver = $this->createResolverWithPaths(['@root/layouts']);

        self::assertSame(
            $this->expectedResolvedPath('layouts', 'hello.php'),
            $resolver->resolveLayout('hello', ['php'])
        );
    }

    #[Test]
    public function resolveLayoutFindsFileInNamespacedPath(): void
    {
        file_put_contents($this->tmpDir . '/widgets/sidebar.php', '<?php');

        $resolver = $this->createResolverWithNamespaces(['widgets' => ['@root/widgets']]);

        self::assertSame(
            $this->expectedResolvedPath('widgets', 'sidebar.php'),
            $resolver->resolveLayout('widgets::sidebar', ['php'])
        );
    }

    #[Test]
    public function resolveLayoutThrowsLayoutNotFoundExceptionWithPathsInMessage(): void
    {
        $resolver = $this->createResolverWithPaths(['@root/layouts']);

        $this->expectException(LayoutNotFoundException::class);
        $this->expectExceptionMessageIsOrContains('Unable to find layout: missing');

        $resolver->resolveLayout('missing', ['php']);
    }

    #[Test]
    public function resolveLayoutCachesResultUntilRefreshed(): void
    {
        $file = $this->tmpDir . '/layouts/cached.php';
        file_put_contents($file, '<?php');

        $resolver = $this->createResolverWithPaths(['@root/layouts']);

        $expected = $this->expectedResolvedPath('layouts', 'cached.php');

        self::assertSame($expected, $resolver->resolveLayout('cached', ['php']));

        unlink($file);

        // Still returns the cached value without touching the filesystem again.
        self::assertSame($expected, $resolver->resolveLayout('cached', ['php']));

        $this->expectException(LayoutNotFoundException::class);

        $resolver->resolveLayout('cached', ['php'], true);
    }

    #[Test]
    public function resolveAliasFollowsChainOfSimpleAliases(): void
    {
        $resolver = $this->createResolverWithAliases(['alpha' => 'beta', 'beta' => 'gamma']);

        self::assertSame('gamma', $resolver->resolveAlias('alpha'));
    }

    #[Test]
    public function resolveAliasHandlesAtPrefixedAliasSegments(): void
    {
        $resolver = $this->createResolverWithAliases(['@theme' => '@realtheme']);

        self::assertSame('@realtheme/partial.foo', $resolver->resolveAlias('@theme/partial.foo'));
    }

    #[Test]
    public function getNamespaceFromPathReturnsNamespaceWhenPresent(): void
    {
        self::assertSame('widgets', LayoutPathResolver::getNamespaceFromPath('widgets::sidebar'));
    }

    #[Test]
    public function getNamespaceFromPathReturnsMainWhenAbsent(): void
    {
        self::assertSame('main', LayoutPathResolver::getNamespaceFromPath('sidebar'));
    }

    #[Test]
    public function getPathsBagLazilyCreatesAndCachesBagPerNamespace(): void
    {
        $resolver = $this->createResolverWithPaths([]);

        $bag1 = $resolver->getPathsBag('ns1');
        $bag2 = $resolver->getPathsBag('ns1');
        $bag3 = $resolver->getPathsBag('ns2');

        self::assertSame($bag1, $bag2);
        self::assertNotSame($bag1, $bag3);
    }

    #[Test]
    public function resetPathsBagDiscardsExistingBagForNamespace(): void
    {
        $resolver = $this->createResolverWithPaths([]);

        $bag1 = $resolver->getPathsBag('ns1');
        $resolver->resetPathsBag('ns1');
        $bag2 = $resolver->getPathsBag('ns1');

        self::assertNotSame($bag1, $bag2);
    }

    #[Test]
    public function addPathsResolvesRelativePathsAndAddsToNamespaceBag(): void
    {
        $resolver = $this->createResolverWithPaths([]);

        $resolver->addPaths(['@root/extra'], null, 'extra');

        self::assertSame(
            [Path::normalize($this->tmpDir . '/extra')],
            $resolver->getPathsBag('extra')->dumpPaths()
        );
    }

    private function expectedResolvedPath(string $dir, string $filename): string
    {
        return Path::normalize($this->tmpDir . '/' . $dir) . '/' . $filename;
    }

    private function createPathResolver(): PathResolver
    {
        return new PathResolver(new Config(['@root' => $this->tmpDir]));
    }

    private function createContainer(array $params = []): Container
    {
        $container = new Container();
        $container->setParameters($params);

        return $container;
    }

    private function createResolverWithPaths(array $paths): LayoutPathResolver
    {
        $container = $this->createContainer(['renderer' => ['paths' => $paths]]);

        return new LayoutPathResolver($container, $this->createPathResolver());
    }

    private function createResolverWithNamespaces(array $namespaces): LayoutPathResolver
    {
        $container = $this->createContainer(['renderer' => ['namespaces' => $namespaces]]);

        return new LayoutPathResolver($container, $this->createPathResolver());
    }

    private function createResolverWithAliases(array $aliases): LayoutPathResolver
    {
        $container = $this->createContainer();

        return new LayoutPathResolver($container, $this->createPathResolver(), $aliases);
    }
}
